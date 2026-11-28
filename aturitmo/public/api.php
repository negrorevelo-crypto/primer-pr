<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$user = require_user();
try {
    $pdo = db();
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['share_history'])) {
        // Sesiones del último año para elegir qué logro compartir. Solo lectura y solo del usuario actual.
        $since = (new DateTimeImmutable('today'))->modify('-365 days')->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('SELECT id, sport_name, duration_minutes, session_detail, distance_km, average_heart_rate, maximum_heart_rate, calories_burned, effort_rating, body_feeling, source_type, occurred_at FROM daily_entries WHERE user_id = :user_id AND entry_type = \'activity\' AND occurred_at >= :since ORDER BY occurred_at DESC, id DESC LIMIT 200');
        $stmt->execute(['user_id' => $user['id'], 'since' => $since]);
        respond(['activities' => $stmt->fetchAll()]);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $profileStmt = $pdo->prepare('SELECT age_range, goal_focus, preferred_sports, available_days_per_week, preferred_session_minutes, subscription_plan, subscription_status FROM users WHERE id = :user_id LIMIT 1');
        $profileStmt->execute(['user_id' => $user['id']]);
        $storedProfile = $profileStmt->fetch() ?: [];
        $now = new DateTimeImmutable('now');
        $today = new DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');
        $weekStart = (new DateTimeImmutable('monday this week'))->setTime(0, 0);
        $historyStart = $weekStart->modify('-3 weeks');
        $recentStart = $today->modify('-6 days');
        $previousStart = $today->modify('-13 days');
        $weeklyGoal = (int) $user['weekly_goal'];
        $preferredSports = json_decode((string) ($storedProfile['preferred_sports'] ?? '[]'), true);
        if (!is_array($preferredSports)) $preferredSports = [];
        $stmt = $pdo->prepare('SELECT id, entry_type, sport_name, duration_minutes, session_detail, distance_km, average_heart_rate, maximum_heart_rate, calories_burned, effort_rating, body_feeling, consumption_status, consumption_timing, alcohol_drinks, sleep_hours, sleep_score, hrv_ms, source_type, source_consent_version, meal_category, content, mood, occurred_at FROM daily_entries WHERE user_id = :user_id AND occurred_at >= :start_at ORDER BY occurred_at DESC, id DESC LIMIT 1500');
        $stmt->execute(['user_id' => $user['id'], 'start_at' => $historyStart->format('Y-m-d H:i:s')]);
        $entries = $stmt->fetchAll();

        $weightStmt = $pdo->prepare('SELECT id, weight_kg, measured_on FROM weight_entries WHERE user_id = :user_id ORDER BY measured_on DESC, id DESC LIMIT 100');
        $weightStmt->execute(['user_id' => $user['id']]);
        $weightHistory = $weightStmt->fetchAll();

        $todayEntries = [];
        $activityHistory = [];
        $todayMinutes = 0;
        $todayDistance = 0.0;
        $todayMeals = 0;
        $weekMinutes = 0;
        $recentActivity = ['minutes' => 0, 'distance_km' => 0.0, 'sessions' => 0, 'days' => [], 'high_effort' => 0, 'recovery_signals' => 0, 'unusual_pain' => 0, 'strength_sessions' => 0];
        $previousActivity = ['minutes' => 0, 'sessions' => 0, 'days' => []];
        $recentMeals = 0;
        $recentMealCategories = [];
        $recentMoods = [];
        $recentSleep = [];
        $previousSleep = [];
        $sleepByDay = [];
        $consumptionByDay = [];
        $sportBreakdown = [];
        $latestSleep = null;
        $latestMood = null;
        $weekTrend = [];
        for ($offset = 3; $offset >= 0; $offset--) {
            $start = $weekStart->modify('-' . $offset . ' weeks');
            $key = $start->format('Y-m-d');
            $weekTrend[$key] = ['start' => $key, 'label' => $offset === 0 ? 'Esta semana' : $start->format('d/m'), 'minutes' => 0, 'distance_km' => 0.0, 'sessions' => 0, 'active_days' => 0, 'goal' => $weeklyGoal, 'days' => []];
        }
        $sleepTrend = [];
        for ($offset = 13; $offset >= 0; $offset--) {
            $date = $today->modify('-' . $offset . ' days');
            $sleepTrend[$date->format('Y-m-d')] = ['date' => $date->format('Y-m-d'), 'label' => $date->format('d/m'), 'hours' => null, 'records' => 0];
        }

        foreach ($entries as $entry) {
            $occurred = new DateTimeImmutable((string) $entry['occurred_at']);
            $dateKey = $occurred->format('Y-m-d');
            $isToday = $occurred >= $today && $occurred < $tomorrow;
            $isRecent = $occurred >= $recentStart;
            $isPrevious = $occurred >= $previousStart && $occurred < $recentStart;
            if ($isToday) $todayEntries[] = $entry;

            if ($entry['entry_type'] === 'activity') {
                $minutes = (int) $entry['duration_minutes'];
                $distance = (float) ($entry['distance_km'] ?? 0);
                $sportName = (string) ($entry['sport_name'] ?: 'Actividad');
                $activityHistory[] = $entry;
                $weekKey = $occurred->modify('monday this week')->format('Y-m-d');
                if (isset($weekTrend[$weekKey])) {
                    $weekTrend[$weekKey]['minutes'] += $minutes;
                    $weekTrend[$weekKey]['distance_km'] += $distance;
                    $weekTrend[$weekKey]['sessions']++;
                    $weekTrend[$weekKey]['days'][$dateKey] = true;
                }
                if ($occurred >= $weekStart) $weekMinutes += $minutes;
                if ($isToday) { $todayMinutes += $minutes; $todayDistance += $distance; }
                if ($isRecent) {
                    $recentActivity['minutes'] += $minutes;
                    $recentActivity['distance_km'] += $distance;
                    $recentActivity['sessions']++;
                    $recentActivity['days'][$dateKey] = true;
                    if ((int) ($entry['effort_rating'] ?? 0) >= 8) $recentActivity['high_effort']++;
                    if (($entry['body_feeling'] ?? '') === 'Más cansancio de lo normal') $recentActivity['recovery_signals']++;
                    if (($entry['body_feeling'] ?? '') === 'Dolor inusual') $recentActivity['unusual_pain']++;
                }
                if ($occurred >= $weekStart && in_array($sportName, ['Gimnasio / fuerza', 'Movilidad o equilibrio'], true)) $recentActivity['strength_sessions']++;
                if ($isPrevious) {
                    $previousActivity['minutes'] += $minutes;
                    $previousActivity['sessions']++;
                    $previousActivity['days'][$dateKey] = true;
                }
                if (!isset($sportBreakdown[$sportName])) $sportBreakdown[$sportName] = ['sport' => $sportName, 'minutes' => 0, 'distance_km' => 0.0, 'sessions' => 0];
                $sportBreakdown[$sportName]['minutes'] += $minutes;
                $sportBreakdown[$sportName]['distance_km'] += $distance;
                $sportBreakdown[$sportName]['sessions']++;
            } elseif ($entry['entry_type'] === 'sleep') {
                $hours = (float) $entry['sleep_hours'];
                $sleepByDay[$dateKey] ??= ['sum' => 0, 'count' => 0, 'tired' => 0];
                $sleepByDay[$dateKey]['sum'] += $hours;
                $sleepByDay[$dateKey]['count']++;
                if (($entry['content'] ?? '') === 'Cansado/a') $sleepByDay[$dateKey]['tired'] = 1;
                if ($isRecent) $recentSleep[] = $hours;
                if ($isPrevious) $previousSleep[] = $hours;
                if ($latestSleep === null) $latestSleep = $entry;
            } elseif ($entry['entry_type'] === 'meal') {
                if ($isToday) $todayMeals++;
                if ($isRecent) {
                    $recentMeals++;
                    $category = (string) ($entry['meal_category'] ?: 'Otra comida');
                    $recentMealCategories[$category] = ($recentMealCategories[$category] ?? 0) + 1;
                }
            } elseif ($entry['entry_type'] === 'mood') {
                if ($isRecent) $recentMoods[(string) $entry['mood']] = ($recentMoods[(string) $entry['mood']] ?? 0) + 1;
                if ($latestMood === null) $latestMood = $entry;
            } elseif ($entry['entry_type'] === 'consumption') {
                $consumptionByDay[$dateKey] ??= (string) ($entry['consumption_status'] ?? '');
            }
        }
        $activityHistory = array_slice($activityHistory, 0, 100);
        foreach ($weekTrend as &$week) {
            $week['active_days'] = count($week['days']);
            $week['distance_km'] = round($week['distance_km'], 2);
            unset($week['days']);
        }
        unset($week);
        foreach ($sportBreakdown as &$sport) $sport['distance_km'] = round($sport['distance_km'], 2);
        unset($sport);
        foreach ($sleepTrend as $dateKey => &$day) {
            if (isset($sleepByDay[$dateKey])) {
                $day['hours'] = round($sleepByDay[$dateKey]['sum'] / $sleepByDay[$dateKey]['count'], 2);
                $day['records'] = $sleepByDay[$dateKey]['count'];
            }
        }
        unset($day);
        usort($sportBreakdown, static fn(array $a, array $b): int => $b['minutes'] <=> $a['minutes']);
        $recentActivity['active_days'] = count($recentActivity['days']);
        $recentActivity['distance_km'] = round($recentActivity['distance_km'], 2);
        $previousActivity['active_days'] = count($previousActivity['days']);
        unset($recentActivity['days'], $previousActivity['days']);
        $sleepAverage = count($recentSleep) ? round(array_sum($recentSleep) / count($recentSleep), 1) : null;
        $previousSleepAverage = count($previousSleep) ? round(array_sum($previousSleep) / count($previousSleep), 1) : null;
        $sleepOnAlcoholDays = [];
        $sleepOnOtherSubstanceDays = [];
        $sleepOnNoConsumptionDays = [];
        $tiredOnAlcoholDays = 0;
        $tiredOnOtherSubstanceDays = 0;
        $tiredOnNoConsumptionDays = 0;
        foreach ($sleepByDay as $dateKey => $sleepDay) {
            if (!array_key_exists($dateKey, $consumptionByDay)) continue;
            $dayAverage = $sleepDay['sum'] / $sleepDay['count'];
            $status = $consumptionByDay[$dateKey];
            if (in_array($status, ['alcohol', 'both'], true)) {
                $sleepOnAlcoholDays[] = $dayAverage;
                $tiredOnAlcoholDays += $sleepDay['tired'];
            }
            if (in_array($status, ['other', 'both'], true)) {
                $sleepOnOtherSubstanceDays[] = $dayAverage;
                $tiredOnOtherSubstanceDays += $sleepDay['tired'];
            }
            if ($status === 'none') {
                $sleepOnNoConsumptionDays[] = $dayAverage;
                $tiredOnNoConsumptionDays += $sleepDay['tired'];
            }
        }
        $consumptionSleepNotes = [];
        if (count($sleepOnAlcoholDays) >= 3 && count($sleepOnNoConsumptionDays) >= 3) {
            $consumptionSleepNotes[] = 'En ' . count($sleepOnAlcoholDays) . ' días con alcohol anotado dormiste en promedio ' . number_format(array_sum($sleepOnAlcoholDays) / count($sleepOnAlcoholDays), 1, ',', '') . ' h (' . $tiredOnAlcoholDays . ' con cansancio al despertar), frente a ' . number_format(array_sum($sleepOnNoConsumptionDays) / count($sleepOnNoConsumptionDays), 1, ',', '') . ' h (' . $tiredOnNoConsumptionDays . ' con cansancio) en ' . count($sleepOnNoConsumptionDays) . ' días sin consumo anotado.';
        }
        if (count($sleepOnOtherSubstanceDays) >= 3 && count($sleepOnNoConsumptionDays) >= 3) {
            $consumptionSleepNotes[] = 'En ' . count($sleepOnOtherSubstanceDays) . ' días con otra opción anotada dormiste en promedio ' . number_format(array_sum($sleepOnOtherSubstanceDays) / count($sleepOnOtherSubstanceDays), 1, ',', '') . ' h (' . $tiredOnOtherSubstanceDays . ' con cansancio al despertar), frente a ' . number_format(array_sum($sleepOnNoConsumptionDays) / count($sleepOnNoConsumptionDays), 1, ',', '') . ' h (' . $tiredOnNoConsumptionDays . ' con cansancio) en ' . count($sleepOnNoConsumptionDays) . ' días sin consumo anotado.';
        }
        $consumptionSleepSummary = $consumptionSleepNotes
            ? implode(' ', $consumptionSleepNotes) . ' Es una comparación personal; no indica que una cosa haya causado la otra.'
            : 'Cuando tengas al menos 3 días comparables con alcohol u otra opción anotados y 3 días sin consumo anotado, mostraremos aquí tus promedios personales de sueño y cansancio. La comparación no determina causas.';
        if ($latestSleep === null) {
            $stmt = $pdo->prepare('SELECT sleep_hours, content, occurred_at FROM daily_entries WHERE user_id = :user_id AND entry_type = \'sleep\' ORDER BY occurred_at DESC, id DESC LIMIT 1');
            $stmt->execute(['user_id' => $user['id']]);
            $latestSleep = $stmt->fetch() ?: null;
        }
        $lastSleep = $latestSleep && new DateTimeImmutable((string) $latestSleep['occurred_at']) >= $now->modify('-36 hours') ? $latestSleep : null;
        $recentMoodEntry = $latestMood && new DateTimeImmutable((string) $latestMood['occurred_at']) >= $now->modify('-36 hours') ? $latestMood : null;
        $remaining = max(0, $weeklyGoal - $weekMinutes);

        if ($recentActivity['unusual_pain'] > 0) {
            $recommendation = [
                'title' => 'Hoy, dale espacio a tu cuerpo.',
                'text' => 'Anotaste un dolor inusual después de una sesión. Considera pausar o evitar el esfuerzo que lo provoca y observa cómo evoluciona. Si el dolor es intenso o viene con hinchazón, debilidad marcada u orina oscura, detén el ejercicio y busca atención médica pronto.',
                'reason' => 'Tomamos en cuenta tu registro de dolor inusual. SumaRitmo no puede determinar su causa ni evaluar una lesión; ante síntomas fuertes, busca valoración profesional.',
            ];
        } elseif (($lastSleep && (float) $lastSleep['sleep_hours'] < 6) || ($recentMoodEntry && $recentMoodEntry['mood'] === 'Cansado/a') || $recentActivity['recovery_signals'] > 0 || ($recentActivity['high_effort'] >= 3 && (($lastSleep && (float) $lastSleep['sleep_hours'] < 7) || ($recentMoodEntry && $recentMoodEntry['mood'] === 'Cansado/a')))) {
            $signals = [];
            if ($lastSleep && (float) $lastSleep['sleep_hours'] < 6) $signals[] = 'anotaste ' . number_format((float) $lastSleep['sleep_hours'], 1, ',', '') . ' horas de sueño';
            if ($recentMoodEntry && $recentMoodEntry['mood'] === 'Cansado/a') $signals[] = 'te registraste como cansado/a';
            if ($recentActivity['recovery_signals'] > 0) $signals[] = 'notaste más cansancio de lo normal después de una sesión';
            if ($recentActivity['high_effort'] >= 3) $signals[] = 'registraste varias sesiones como muy exigentes';
            $recommendation = [
                'title' => 'Escucharte también es avanzar.',
                'text' => 'Tus registros recientes sugieren que podrías necesitar un poco más de recuperación. Si hoy te mueves, considera una sesión suave o una pausa. No tienes que compensar una sesión ni una mala noche.',
                'reason' => 'Tomamos en cuenta que ' . implode(' y ', $signals) . '. Es una orientación basada en lo que registraste, no una medición clínica de recuperación.',
            ];
        } elseif ((($user['goal_focus'] ?? '') === 'fuerza' || in_array(($user['age_range'] ?? ''), ['60-64', '65-74', '75+'], true)) && $recentActivity['strength_sessions'] === 0 && $recentActivity['sessions'] > 0) {
            $recommendation = [
                'title' => 'También puedes construir fuerza a tu manera.',
                'text' => 'Esta semana registraste movimiento, pero todavía no una sesión de fuerza o movilidad. Si te resulta adecuado, podrías explorar una actividad adaptada a tu experiencia y a cómo te sientes.',
                'reason' => 'Tomamos en cuenta tu objetivo y/o tu rango de edad, además de que aún no aparecen sesiones de fuerza o movilidad en tus registros de esta semana. Es una idea general, no una prescripción; elige una opción adecuada para ti.',
            ];
        } elseif ($weekMinutes >= $weeklyGoal) {
            $recommendation = [
                'title' => 'Mira cuánto has sumado a tu propio camino.',
                'text' => 'Llevas ' . $weekMinutes . ' minutos esta semana y alcanzaste la meta que elegiste. Reconoce tu avance y decide con calma qué te viene bien ahora.',
                'reason' => 'Sumamos ' . $weekMinutes . ' minutos de actividad desde el lunes y los comparamos con tu meta personal de ' . $weeklyGoal . ' minutos. No es una comparación con otras personas.',
            ];
        } elseif ($todayMinutes > 0) {
            $recommendation = [
                'title' => 'Hoy ya hiciste espacio para moverte.',
                'text' => 'Llevas ' . $todayMinutes . ' minutos registrados hoy. Disfruta tu sesión; no necesitas añadir más para que cuente.',
                'reason' => 'Tomamos en cuenta tus registros de hoy y los ' . $weekMinutes . ' minutos que llevas esta semana.',
            ];
        } elseif ($recentActivity['minutes'] > 0) {
            $sportHint = $preferredSports[0] ?? ($sportBreakdown[0]['sport'] ?? null);
            $sessionHint = !empty($storedProfile['preferred_session_minutes']) ? ' de unos ' . (int) $storedProfile['preferred_session_minutes'] . ' minutos' : '';
            $nextStep = $sportHint
                ? ' Si hoy te apetece moverte, podrías elegir ' . $sportHint . $sessionHint . ' a tu manera.'
                : ' Si hoy te apetece, elige una actividad que disfrutes.';
            $recommendation = [
                'title' => 'Vas avanzando desde tu propio punto de partida.',
                'text' => 'Llevas ' . $weekMinutes . ' de ' . $weeklyGoal . ' minutos en la meta que elegiste.' . $nextStep . ' No tienes que recuperar todo de una vez.',
                'reason' => 'En los últimos 7 días registraste ' . $recentActivity['minutes'] . ' minutos en ' . $recentActivity['sessions'] . ' sesiones y ' . $recentActivity['active_days'] . ' días con movimiento.' . ($sportHint ? ' En las últimas 4 semanas anotaste más sesiones de ' . $sportHint . '.' : '') . ' La meta es una guía que tú elegiste; SumaRitmo aún no evalúa intensidad ni prescribe un plan técnico.',
            ];
        } else {
            $recommendation = [
                'title' => '¿Qué te gustaría hacer hoy?',
                'text' => 'Puedes registrar un deporte que ya practicas, cómo dormiste o qué comiste. Cuando tengamos más días anotados, veremos patrones contigo.',
                'reason' => 'Todavía hay pocos registros de actividad recientes para sugerir un siguiente paso personal. Puedes añadir tus datos cuando quieras.',
            ];
        }

        $daysWithAnyData = count(array_unique(array_map(static fn(array $entry): string => substr((string) $entry['occurred_at'], 0, 10), array_filter($entries, static fn(array $entry): bool => new DateTimeImmutable((string) $entry['occurred_at']) >= $recentStart))));
        respond([
            'profile' => ['age_range' => $storedProfile['age_range'] ?? '', 'goal_focus' => $storedProfile['goal_focus'] ?? 'bienestar', 'preferred_sports' => $preferredSports, 'available_days_per_week' => $storedProfile['available_days_per_week'] ?? null, 'preferred_session_minutes' => $storedProfile['preferred_session_minutes'] ?? null],
            'plan' => ['code' => $storedProfile['subscription_plan'] ?? 'nivel_1', 'status' => $storedProfile['subscription_status'] ?? 'pilot'],
            'baseline' => ['days_with_data' => $daysWithAnyData, 'days_target' => 7],
            'today' => ['entries' => $todayEntries, 'minutes' => $todayMinutes, 'distance_km' => round($todayDistance, 2), 'meals' => $todayMeals],
            'week' => ['minutes' => $weekMinutes, 'goal' => $weeklyGoal, 'activities' => $activityHistory],
            'weight' => ['latest' => $weightHistory[0] ?? null, 'history' => $weightHistory],
            'trends' => [
                'weeks' => array_values($weekTrend),
                'recent_activity' => $recentActivity,
                'previous_activity' => $previousActivity,
                'sleep_average' => $sleepAverage,
                'sleep_records' => count($recentSleep),
                'previous_sleep_average' => $previousSleepAverage,
                'sleep_days' => array_values($sleepTrend),
                'meal_records' => $recentMeals,
                'meal_categories' => (object) $recentMealCategories,
                'mood_records' => array_sum($recentMoods),
                'mood_counts' => (object) $recentMoods,
                'sports' => array_values($sportBreakdown),
                'consumption_sleep_summary' => $consumptionSleepSummary,
            ],
            'last_sleep' => $latestSleep,
            'recommendation' => $recommendation,
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['error' => 'Método no disponible.'], 405);
    }
    require_csrf();
    $raw = file_get_contents('php://input') ?: '';
    if (strlen($raw) > 524288) respond(['error' => 'El archivo tiene demasiados datos para importarlo de una sola vez.'], 413);
    $payload = json_decode($raw, true);
    if (!is_array($payload)) respond(['error' => 'No pudimos leer la información.'], 400);
    $action = (string) ($payload['action'] ?? '');

    if ($action === 'garmin_csv_import') {
        $items = $payload['entries'] ?? null;
        if (!is_array($items) || count($items) < 1 || count($items) > 500) {
            respond(['error' => 'El archivo debe incluir entre 1 y 500 actividades recientes.'], 422);
        }
        $allowedSports = ['Fútbol', 'Pádel', 'Tenis', 'Natación', 'Correr', 'Ciclismo', 'Gimnasio / fuerza', 'Movilidad o equilibrio', 'Caminata', 'Baloncesto', 'Baile', 'Yoga', 'Otro'];
        $importTimezone = new DateTimeZone('America/Bogota');
        $today = new DateTimeImmutable('today', $importTimezone);
        $minimumDate = $today->modify('-59 days');
        $normalizedItems = [];
        foreach ($items as $item) {
            if (!is_array($item)) respond(['error' => 'Hay una fila del CSV que no pudimos leer.'], 422);
            $dateRaw = trim((string) ($item['entry_date'] ?? ''));
            $entryDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateRaw, $importTimezone);
            $dateErrors = DateTimeImmutable::getLastErrors();
            $sport = trim((string) ($item['sport_name'] ?? ''));
            $duration = filter_var($item['duration_minutes'] ?? null, FILTER_VALIDATE_INT);
            $distanceRaw = $item['distance_km'] ?? null;
            $distance = $distanceRaw === null || $distanceRaw === '' ? null : filter_var(str_replace(',', '.', (string) $distanceRaw), FILTER_VALIDATE_FLOAT);
            $optionalInteger = static function (array $source, string $key) {
                if (!isset($source[$key]) || $source[$key] === '') return null;
                $value = filter_var($source[$key], FILTER_VALIDATE_INT);
                return $value === false ? false : $value;
            };
            $averageHeartRate = $optionalInteger($item, 'average_heart_rate');
            $maximumHeartRate = $optionalInteger($item, 'maximum_heart_rate');
            $calories = $optionalInteger($item, 'calories_burned');
            $detail = trim((string) ($item['session_detail'] ?? ''));
            if ($entryDate === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
                || $entryDate->format('Y-m-d') !== $dateRaw || $entryDate < $minimumDate || $entryDate > $today
                || !in_array($sport, $allowedSports, true) || $duration === false || $duration < 1 || $duration > 1440 || strlen($detail) > 160
                || ($distance !== null && ($distance === false || $distance <= 0 || $distance > 2000))
                || ($averageHeartRate !== null && ($averageHeartRate === false || $averageHeartRate < 30 || $averageHeartRate > 250))
                || ($maximumHeartRate !== null && ($maximumHeartRate === false || $maximumHeartRate < 30 || $maximumHeartRate > 250))
                || ($calories !== null && ($calories === false || $calories < 1 || $calories > 20000))) {
                respond(['error' => 'Una actividad no tiene fecha reciente o contiene datos inválidos. Revisa el CSV y vuelve a intentarlo.'], 422);
            }
            $normalizedItems[] = [
                'entry_date' => $entryDate->format('Y-m-d'), 'sport_name' => $sport, 'duration_minutes' => $duration,
                'distance_km' => $distance, 'average_heart_rate' => $averageHeartRate, 'maximum_heart_rate' => $maximumHeartRate,
                'calories_burned' => $calories, 'session_detail' => $detail,
            ];
        }

        $insert = $pdo->prepare('INSERT INTO daily_entries (user_id, entry_type, sport_name, duration_minutes, session_detail, distance_km, average_heart_rate, maximum_heart_rate, calories_burned, source_type, source_record_key, occurred_at) VALUES (:user_id, \'activity\', :sport_name, :duration_minutes, :session_detail, :distance_km, :average_heart_rate, :maximum_heart_rate, :calories_burned, \'file\', :source_record_key, :occurred_at)');
        $existing = $pdo->prepare('SELECT id FROM daily_entries WHERE user_id = :user_id AND entry_type = \'activity\' AND DATE(occurred_at) = :entry_date AND sport_name = :sport_name AND duration_minutes = :duration_minutes AND distance_km <=> :distance_km AND average_heart_rate <=> :average_heart_rate AND maximum_heart_rate <=> :maximum_heart_rate AND calories_burned <=> :calories_burned AND COALESCE(session_detail, \'\') = :session_detail LIMIT 1');
        $imported = 0;
        $duplicates = 0;
        $pdo->beginTransaction();
        try {
            foreach ($normalizedItems as $item) {
                $canonical = [
                    (string) $user['id'], $item['entry_date'], $item['sport_name'], (string) $item['duration_minutes'],
                    $item['distance_km'] === null ? '' : number_format((float) $item['distance_km'], 2, '.', ''),
                    $item['average_heart_rate'] === null ? '' : (string) $item['average_heart_rate'],
                    $item['maximum_heart_rate'] === null ? '' : (string) $item['maximum_heart_rate'],
                    $item['calories_burned'] === null ? '' : (string) $item['calories_burned'], $item['session_detail'],
                ];
                $recordKey = hash('sha256', json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $existing->execute([
                    'user_id' => $user['id'], 'entry_date' => $item['entry_date'], 'sport_name' => $item['sport_name'],
                    'duration_minutes' => $item['duration_minutes'], 'distance_km' => $item['distance_km'],
                    'average_heart_rate' => $item['average_heart_rate'], 'maximum_heart_rate' => $item['maximum_heart_rate'],
                    'calories_burned' => $item['calories_burned'], 'session_detail' => $item['session_detail'],
                ]);
                if ($existing->fetchColumn() !== false) { $duplicates++; continue; }
                try {
                    $insert->execute([
                        'user_id' => $user['id'], 'sport_name' => $item['sport_name'], 'duration_minutes' => $item['duration_minutes'],
                        'session_detail' => $item['session_detail'], 'distance_km' => $item['distance_km'],
                        'average_heart_rate' => $item['average_heart_rate'], 'maximum_heart_rate' => $item['maximum_heart_rate'],
                        'calories_burned' => $item['calories_burned'], 'source_record_key' => $recordKey,
                        'occurred_at' => $item['entry_date'] . ' ' . (new DateTimeImmutable('now', $importTimezone))->format('H:i:s'),
                    ]);
                    $imported++;
                } catch (PDOException $exception) {
                    if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) throw $exception;
                    $duplicates++;
                }
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }
        respond(['ok' => true, 'imported' => $imported, 'duplicates' => $duplicates, 'message' => 'Importación del CSV terminada.']);
    }

    if ($action === 'goal') {
        $goal = filter_var($payload['weekly_goal'] ?? null, FILTER_VALIDATE_INT);
        if ($goal === false || $goal < 30 || $goal > 1200 || $goal % 15 !== 0) {
            respond(['error' => 'Elige una meta entre 30 y 1200 minutos, en intervalos de 15.'], 422);
        }
        $stmt = $pdo->prepare('UPDATE users SET weekly_activity_goal_minutes = :goal WHERE id = :user_id');
        $stmt->execute(['goal' => $goal, 'user_id' => $user['id']]);
        $_SESSION['weekly_goal'] = $goal;
        respond(['ok' => true, 'message' => 'Meta semanal guardada.']);
    }

    if ($action === 'profile') {
        $ageRange = trim((string) ($payload['age_range'] ?? ''));
        $goalFocus = trim((string) ($payload['goal_focus'] ?? 'bienestar'));
        $sportsRaw = $payload['preferred_sports'] ?? [];
        $preferredSports = is_array($sportsRaw) ? array_values(array_unique(array_filter(array_map(static fn($sport): string => trim((string) $sport), $sportsRaw)))) : [];
        $availableDays = ($payload['available_days_per_week'] ?? '') === '' ? null : filter_var($payload['available_days_per_week'], FILTER_VALIDATE_INT);
        $sessionMinutes = ($payload['preferred_session_minutes'] ?? '') === '' ? null : filter_var($payload['preferred_session_minutes'], FILTER_VALIDATE_INT);
        $allowedAgeRanges = ['18-29', '30-44', '45-59', '60-64', '65-74', '75+'];
        $allowedFocuses = ['bienestar', 'constancia', 'fuerza', 'rendimiento'];
        $allowedSports = ['Fútbol', 'Pádel', 'Tenis', 'Natación', 'Correr', 'Ciclismo', 'Gimnasio / fuerza', 'Movilidad o equilibrio', 'Caminata', 'Baloncesto', 'Baile', 'Yoga', 'Otro'];
        if (!in_array($ageRange, $allowedAgeRanges, true) || !in_array($goalFocus, $allowedFocuses, true)
            || count($preferredSports) > count($allowedSports) || array_diff($preferredSports, $allowedSports)
            || ($availableDays !== null && ($availableDays === false || $availableDays < 1 || $availableDays > 7))
            || ($sessionMinutes !== null && ($sessionMinutes === false || $sessionMinutes < 10 || $sessionMinutes > 360))) {
            respond(['error' => 'Revisa tu objetivo, deportes y tiempo disponible.'], 422);
        }
        $sportsJson = json_encode($preferredSports, JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare('UPDATE users SET age_range = :age_range, goal_focus = :goal_focus, preferred_sports = :preferred_sports, available_days_per_week = :available_days, preferred_session_minutes = :session_minutes WHERE id = :user_id');
        $stmt->execute(['age_range' => $ageRange, 'goal_focus' => $goalFocus, 'preferred_sports' => $sportsJson, 'available_days' => $availableDays, 'session_minutes' => $sessionMinutes, 'user_id' => $user['id']]);
        $_SESSION['age_range'] = $ageRange;
        $_SESSION['goal_focus'] = $goalFocus;
        respond(['ok' => true, 'message' => 'Perfil guardado. Ajustaremos el contexto a tu objetivo.']);
    }

    if ($action === 'delete_account') {
        if (($payload['confirm'] ?? false) !== true) respond(['error' => 'Confirma que deseas borrar la cuenta.'], 422);
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :user_id');
        $stmt->execute(['user_id' => $user['id']]);
        $_SESSION = [];
        session_destroy();
        respond(['ok' => true, 'redirect' => 'index.php?eliminada=1']);
    }

    if ($action === 'delete_entry') {
        $entryId = filter_var($payload['entry_id'] ?? null, FILTER_VALIDATE_INT);
        if ($entryId === false || $entryId < 1) respond(['error' => 'No encontramos ese registro.'], 422);
        $stmt = $pdo->prepare('DELETE FROM daily_entries WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $entryId, 'user_id' => $user['id']]);
        if ($stmt->rowCount() !== 1) respond(['error' => 'Ese registro ya no está disponible.'], 404);
        respond(['ok' => true, 'message' => 'Registro eliminado.']);
    }

    if ($action === 'weight') {
        $weightRaw = $payload['weight_kg'] ?? null;
        $weight = filter_var($weightRaw, FILTER_VALIDATE_FLOAT);
        $measuredOn = trim((string) ($payload['measured_on'] ?? ''));
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $measuredOn);
        $dateErrors = DateTimeImmutable::getLastErrors();
        if ($weight === false || $weight < 1 || $weight > 500 || $date === false
            || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
            || $date->format('Y-m-d') !== $measuredOn || $measuredOn > (new DateTimeImmutable('today'))->format('Y-m-d')) {
            respond(['error' => 'Indica un peso entre 1 y 500 kg y una fecha válida que no sea futura.'], 422);
        }
        $stmt = $pdo->prepare('INSERT INTO weight_entries (user_id, weight_kg, measured_on) VALUES (:user_id, :weight_kg, :measured_on)');
        $stmt->execute(['user_id' => $user['id'], 'weight_kg' => round((float) $weight, 2), 'measured_on' => $measuredOn]);
        respond(['ok' => true, 'message' => 'Pesaje guardado.']);
    }

    if ($action === 'delete_weight') {
        $weightId = filter_var($payload['weight_id'] ?? null, FILTER_VALIDATE_INT);
        if ($weightId === false || $weightId < 1) respond(['error' => 'No encontramos ese pesaje.'], 422);
        $stmt = $pdo->prepare('DELETE FROM weight_entries WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $weightId, 'user_id' => $user['id']]);
        if ($stmt->rowCount() !== 1) respond(['error' => 'Ese pesaje ya no está disponible.'], 404);
        respond(['ok' => true, 'message' => 'Pesaje eliminado.']);
    }

    if ($action !== 'entry') respond(['error' => 'No reconocemos esta acción.'], 400);
    $type = (string) ($payload['entry_type'] ?? '');
    $allowedSports = ['Fútbol', 'Pádel', 'Tenis', 'Natación', 'Correr', 'Ciclismo', 'Gimnasio / fuerza', 'Movilidad o equilibrio', 'Caminata', 'Baloncesto', 'Baile', 'Yoga', 'Otro'];
        $mealCategories = ['Desayuno', 'Almuerzo', 'Cena', 'Algo entre comidas', 'Bebida sin alcohol'];
    $sport = null;
    $duration = null;
    $detail = null;
    $effort = null;
    $bodyFeeling = null;
    $consumptionStatus = null;
    $consumptionTiming = null;
    $alcoholDrinks = null;
    $consumptionConsentVersion = null;
    $sleep = null;
    $meal = null;
    $content = null;
    $mood = null;
    $distance = null;
    $averageHeartRate = null;
    $maximumHeartRate = null;
    $calories = null;
    $sleepScore = null;
    $hrv = null;
    $sourceType = trim((string) ($payload['source_type'] ?? 'manual'));
    $sourceConsentVersion = null;
    $allowedSources = ['manual', 'screenshot', 'file'];
    if (!in_array($sourceType, $allowedSources, true)) $sourceType = 'manual';
    if ($sourceType === 'screenshot') {
        $captureConsent = ($payload['capture_consent'] ?? false) === true;
        if (!$captureConsent) respond(['error' => 'Confirma la autorización para procesar esta captura.'], 422);
        $sourceConsentVersion = '2026-11-22-v1';
    }
    $entryDateRaw = trim((string) ($payload['entry_date'] ?? ''));
    $entryDate = $entryDateRaw === '' ? new DateTimeImmutable('today') : DateTimeImmutable::createFromFormat('!Y-m-d', $entryDateRaw);
    $entryDateErrors = DateTimeImmutable::getLastErrors();
    if ($entryDate === false || ($entryDateErrors !== false && ($entryDateErrors['warning_count'] > 0 || $entryDateErrors['error_count'] > 0))
        || $entryDate->format('Y-m-d') !== $entryDateRaw && $entryDateRaw !== ''
        || $entryDate > new DateTimeImmutable('today') || $entryDate < (new DateTimeImmutable('today'))->modify('-120 days')) {
        respond(['error' => 'Elige una fecha de hoy o de los últimos 120 días.'], 422);
    }

    if ($type === 'activity') {
        $sport = trim((string) ($payload['sport_name'] ?? ''));
        $duration = filter_var($payload['duration_minutes'] ?? null, FILTER_VALIDATE_INT);
        $detail = trim((string) ($payload['session_detail'] ?? ''));
        $distance = ($payload['distance_km'] ?? '') === '' ? null : filter_var(str_replace(',', '.', (string) $payload['distance_km']), FILTER_VALIDATE_FLOAT);
        $averageHeartRate = ($payload['average_heart_rate'] ?? '') === '' ? null : filter_var($payload['average_heart_rate'], FILTER_VALIDATE_INT);
        $maximumHeartRate = ($payload['maximum_heart_rate'] ?? '') === '' ? null : filter_var($payload['maximum_heart_rate'], FILTER_VALIDATE_INT);
        $calories = ($payload['calories_burned'] ?? '') === '' ? null : filter_var($payload['calories_burned'], FILTER_VALIDATE_INT);
        $effortRaw = $payload['effort_rating'] ?? '';
        $effort = $effortRaw === '' ? null : filter_var($effortRaw, FILTER_VALIDATE_INT);
        $bodyFeeling = trim((string) ($payload['body_feeling'] ?? ''));
        if ($bodyFeeling === '') $bodyFeeling = null;
        if (!in_array($sport, $allowedSports, true) || $duration === false || $duration < 1 || $duration > 1440 || strlen($detail) > 160
            || ($distance !== null && ($distance === false || $distance <= 0 || $distance > 2000))
            || ($averageHeartRate !== null && ($averageHeartRate === false || $averageHeartRate < 30 || $averageHeartRate > 250))
            || ($maximumHeartRate !== null && ($maximumHeartRate === false || $maximumHeartRate < 30 || $maximumHeartRate > 250))
            || ($calories !== null && ($calories === false || $calories < 1 || $calories > 20000))
            || ($effort !== null && ($effort === false || $effort < 1 || $effort > 10)) || ($bodyFeeling !== null && !in_array($bodyFeeling, ['Bien', 'Cansancio habitual', 'Más cansancio de lo normal', 'Dolor inusual'], true))) {
            respond(['error' => 'Revisa el deporte y la duración de tu sesión.'], 422);
        }
    } elseif ($type === 'sleep') {
        $sleep = filter_var($payload['sleep_hours'] ?? null, FILTER_VALIDATE_FLOAT);
        $sleepScore = ($payload['sleep_score'] ?? '') === '' ? null : filter_var($payload['sleep_score'], FILTER_VALIDATE_INT);
        $hrv = ($payload['hrv_ms'] ?? '') === '' ? null : filter_var(str_replace(',', '.', (string) $payload['hrv_ms']), FILTER_VALIDATE_FLOAT);
        $content = trim((string) ($payload['content'] ?? ''));
        if ($sleep === false || $sleep < 0.5 || $sleep > 24 || ($sleepScore !== null && ($sleepScore === false || $sleepScore < 0 || $sleepScore > 100))
            || ($hrv !== null && ($hrv === false || $hrv < 1 || $hrv > 500)) || !in_array($content, ['Descansado/a', 'Más o menos', 'Cansado/a'], true)) {
            respond(['error' => 'Revisa las horas de sueño y cómo te sientes.'], 422);
        }
    } elseif ($type === 'meal') {
        $content = trim((string) ($payload['content'] ?? ''));
        $meal = trim((string) ($payload['meal_category'] ?? ''));
        $alcoholDrinkPattern = '/(*UCP)(?<![\p{L}\p{N}_])(?:(?:\d+|una?|dos|tres)\s+)?(?:copas?|copitas?|vasos?|botellas?|tragos?|beb[ií]|tom[eé]|consum[ií]|tomando|bebiendo)\s+(?:de\s+)?(?:(?:un|una)\s+)?(?:vino|cerveza|sidra|ron|whisky|whiskey|vodka|aguardiente|tequila|ginebra|gin|brandy|pisco|vermut|sangr[ií]a|licor|champ[aá]n|champagne|c[oó]ctel(?:es)?)(?=$|[^\p{L}\p{N}_])/iu';
        $mentionsAlcoholDrink = preg_match($alcoholDrinkPattern, $content) === 1;
        if ($content === '' || mb_strlen($content) > 500 || !in_array($meal, $mealCategories, true) || $mentionsAlcoholDrink) {
            $message = $mentionsAlcoholDrink
                ? 'Este espacio es para registrar comida y bebidas sin alcohol.'
                : 'Anota qué comiste o bebiste y elige el tipo de registro.';
            respond(['error' => $message], 422);
        }
    } elseif ($type === 'mood') {
        $mood = trim((string) ($payload['mood'] ?? ''));
        $content = trim((string) ($payload['content'] ?? ''));
        if (!in_array($mood, ['Con energía', 'Bien', 'Más o menos', 'Cansado/a'], true) || mb_strlen($content) > 500) {
            respond(['error' => 'Elige cómo te sientes y revisa la nota.'], 422);
        }
    } elseif ($type === 'consumption') {
        $consumptionStatus = trim((string) ($payload['consumption_status'] ?? ''));
        $consumptionTiming = trim((string) ($payload['consumption_timing'] ?? ''));
        $alcoholRaw = $payload['alcohol_drinks'] ?? '';
        $alcoholDrinks = $alcoholRaw === '' ? null : filter_var($alcoholRaw, FILTER_VALIDATE_INT);
        $allowedStatuses = ['none', 'alcohol', 'other', 'both', 'prefer_not'];
        $allowedTimings = ['', 'before_sleep', 'earlier'];
        $hasAlcohol = in_array($consumptionStatus, ['alcohol', 'both'], true);
        $consent = ($payload['sensitive_consent'] ?? false) === true || ($payload['sensitive_consent'] ?? '') === '1';
        $requiresConsent = in_array($consumptionStatus, ['alcohol', 'other', 'both'], true);
        if (!in_array($consumptionStatus, $allowedStatuses, true) || !in_array($consumptionTiming, $allowedTimings, true)
            || ($alcoholDrinks !== null && ($alcoholDrinks === false || $alcoholDrinks < 1 || $alcoholDrinks > 60))
            || (!$hasAlcohol && $alcoholDrinks !== null) || ($requiresConsent && !$consent)) {
            respond(['error' => 'Revisa la opción elegida, la cantidad aproximada y la autorización de este registro.'], 422);
        }
        $consumptionConsentVersion = $requiresConsent ? '2026-11-21-v2' : null;
    } else {
        respond(['error' => 'Tipo de registro no disponible.'], 422);
    }

    $occurredAt = $entryDate->format('Y-m-d') . ' ' . (new DateTimeImmutable('now'))->format('H:i:s');
    $stmt = $pdo->prepare('INSERT INTO daily_entries (user_id, entry_type, sport_name, duration_minutes, session_detail, distance_km, average_heart_rate, maximum_heart_rate, calories_burned, effort_rating, body_feeling, consumption_status, consumption_timing, alcohol_drinks, consumption_consent_version, sleep_hours, sleep_score, hrv_ms, source_type, source_consent_version, meal_category, content, mood, occurred_at) VALUES (:user_id, :entry_type, :sport_name, :duration_minutes, :session_detail, :distance_km, :average_heart_rate, :maximum_heart_rate, :calories_burned, :effort_rating, :body_feeling, :consumption_status, :consumption_timing, :alcohol_drinks, :consumption_consent_version, :sleep_hours, :sleep_score, :hrv_ms, :source_type, :source_consent_version, :meal_category, :content, :mood, :occurred_at)');
    $stmt->execute([
        'user_id' => $user['id'], 'entry_type' => $type, 'sport_name' => $sport,
        'duration_minutes' => $duration, 'session_detail' => $detail, 'distance_km' => $distance,
        'average_heart_rate' => $averageHeartRate, 'maximum_heart_rate' => $maximumHeartRate, 'calories_burned' => $calories, 'effort_rating' => $effort,
        'body_feeling' => $bodyFeeling, 'consumption_status' => $consumptionStatus,
        'consumption_timing' => $consumptionTiming, 'alcohol_drinks' => $alcoholDrinks,
        'consumption_consent_version' => $consumptionConsentVersion, 'sleep_hours' => $sleep, 'sleep_score' => $sleepScore, 'hrv_ms' => $hrv, 'source_type' => $sourceType, 'source_consent_version' => $sourceConsentVersion,
        'meal_category' => $meal, 'content' => $content, 'mood' => $mood,
        'occurred_at' => $occurredAt,
    ]);
    respond(['ok' => true, 'message' => $type === 'activity' ? 'Sesión guardada. Cada actividad suma.' : ($type === 'consumption' ? 'Registro guardado solo para observar tus propios patrones.' : 'Registro guardado. Gracias por contarlo.')]);
} catch (Throwable $exception) {
    error_log('SumaRitmo API failure: ' . $exception->getMessage());
    respond(['error' => 'No pudimos guardar o consultar tus datos. Inténtalo de nuevo.'], 500);
}
