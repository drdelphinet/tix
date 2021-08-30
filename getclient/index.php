<?php
require 'script.php';

$cliente = new Api();
print_r($cliente->all($_GET['cpf']));
