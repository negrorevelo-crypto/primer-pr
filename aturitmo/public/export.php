<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
$user = current_user();
if (!$user) {
    header('Location: index.php');
    exit;
}
try {
    $stmt = db()->prepare('SELECT entry_type, sport_name, duration_minutes, session_detail, distance_km, average_heart_rate, maximum_heart_rate, calories_burned, effort_rating, body_feeling, consumption_status, consumption_timing, alcohol_drinks, consumption_consent_version, sleep_hours, sleep_score, hrv_ms, source_type, source_consent_version, meal_category, content, mood, occurred_at FROM daily_entries WHERE user_id = :user_id ORDER BY occurred_at DESC, id DESC');
    $stmt->execute(['user_id' => $user['id']]);
    $rows = $stmt->fetchAll();
    $profileStmt = db()->prepare('SELECT preferred_sports, available_days_per_week, preferred_session_minutes, subscription_plan, subscription_status FROM users WHERE id = :user_id LIMIT 1');
    $profileStmt->execute(['user_id' => $user['id']]);
    $profile = $profileStmt->fetch() ?: [];
    $weightStmt = db()->prepare('SELECT weight_kg, measured_on FROM weight_entries WHERE user_id = :user_id ORDER BY measured_on DESC, id DESC');
    $weightStmt->execute(['user_id' => $user['id']]);
    $weightRows = $weightStmt->fetchAll();
} catch (Throwable $exception) {
    error_log('SumaRitmo export failure: ' . $exception->getMessage());
    http_response_code(503);
    echo 'No pudimos preparar tu descarga. Inténtalo de nuevo más tarde.';
    exit;
}

function csv_safe(?string $value): string
{
    $value = (string) $value;
    return preg_match('/^[\s]*[=+@\-]/u', $value) ? "'" . $value : $value;
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sumaritmo-registros-' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store, private');
$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Fecha y hora', 'Tipo', 'Deporte o actividad', 'Duración (min)', 'Distancia (km)', 'FC promedio (lpm)', 'FC máxima (lpm)', 'Calorías', 'Esfuerzo percibido (1-10)', 'Cómo quedó el cuerpo', 'Consumo anotado', 'Momento respecto al descanso', 'Bebidas alcohólicas aprox.', 'Versión de autorización de consumo', 'Detalle de sesión', 'Sueño (h)', 'Puntaje de sueño', 'HRV (ms)', 'Fuente del registro', 'Versión de autorización de captura', 'Comida', 'Registro', 'Ánimo', 'Rango de edad', 'Objetivo personal', 'Deportes preferidos', 'Días disponibles/semana', 'Minutos habituales/sesión', 'Plan', 'Estado del plan', 'Peso (kg)', 'Fecha del pesaje']);
foreach ($rows as $row) {
    $consumptionLabels = ['none' => 'Sin consumo anotado', 'alcohol' => 'Alcohol', 'other' => 'Otra sustancia', 'both' => 'Alcohol y otra sustancia', 'prefer_not' => 'Prefirió no responder'];
    $timingLabels = ['before_sleep' => 'En las horas antes de dormir', 'earlier' => 'Más temprano'];
    fputcsv($output, [
        $row['occurred_at'], $row['entry_type'], csv_safe($row['sport_name']), $row['duration_minutes'], $row['distance_km'],
        $row['average_heart_rate'], $row['maximum_heart_rate'], $row['calories_burned'], $row['effort_rating'], csv_safe($row['body_feeling']),
        $consumptionLabels[$row['consumption_status'] ?? ''] ?? '', $timingLabels[$row['consumption_timing'] ?? ''] ?? '', $row['alcohol_drinks'],
        csv_safe($row['consumption_consent_version']), csv_safe($row['session_detail']), $row['sleep_hours'], $row['sleep_score'], $row['hrv_ms'],
        csv_safe($row['source_type']), csv_safe($row['source_consent_version']), csv_safe($row['meal_category']), csv_safe($row['content']), csv_safe($row['mood']), csv_safe($user['age_range']),
        csv_safe($user['goal_focus']), csv_safe((string) ($profile['preferred_sports'] ?? '')), $profile['available_days_per_week'] ?? '',
        $profile['preferred_session_minutes'] ?? '', csv_safe((string) ($profile['subscription_plan'] ?? 'nivel_1')),
        csv_safe((string) ($profile['subscription_status'] ?? 'pilot')), '', '',
    ]);
}
foreach ($weightRows as $row) {
    fputcsv($output, array_merge(
        [$row['measured_on'], 'Peso'],
        array_fill(0, 21, ''),
        [csv_safe($user['age_range']), csv_safe($user['goal_focus'])],
        array_fill(0, 5, ''),
        [$row['weight_kg'], $row['measured_on']]
    ));
}
fclose($output);
