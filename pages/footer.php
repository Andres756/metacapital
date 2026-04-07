</main><!-- /content -->
</div><!-- /layout -->

<script src="/assets/js/app.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/js/app.js') ?>"></script>
<script>
// Cambiar cobro vía AJAX
async function cambiarCobro(id) {
  const res = await apiPost('/api/set_cobro.php', { cobro_id: id });
  if (res.ok) { window.location.reload(); }
  else { toast(res.msg || 'Error al cambiar cobro', 'error'); }
}
</script>
<?= $extraScript ?? '' ?>
</body>
</html>