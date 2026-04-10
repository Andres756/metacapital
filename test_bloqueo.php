<?php
require_once __DIR__ . '/config/db.php';

$db = getDB();
$id = 9;

$stmt = $db->prepare("SELECT id, cobrador_bloqueado FROM liquidaciones WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch();
echo "ANTES: cobrador_bloqueado=" . $row['cobrador_bloqueado'] . "\n";

$nuevo = $row['cobrador_bloqueado'] ? 0 : 1;
$upd = $db->prepare("UPDATE liquidaciones SET cobrador_bloqueado=? WHERE id=?");
$upd->execute([$nuevo, $id]);
echo "Rows affected: " . $upd->rowCount() . "\n";

$stmt2 = $db->prepare("SELECT id, cobrador_bloqueado FROM liquidaciones WHERE id=?");
$stmt2->execute([$id]);
$row2 = $stmt2->fetch();
echo "DESPUÉS: cobrador_bloqueado=" . $row2['cobrador_bloqueado'] . "\n";