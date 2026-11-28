(() => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('aviso') !== 'usuario_no_existe') return;

  window.alert('El usuario no existe. Te enviaremos a la página principal.');
  window.location.replace('index.php');
})();
