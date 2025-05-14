<?php
header('Content-Type: application/javascript');
echo "window.ENV = {";
echo "  OPENROUTER_API_KEY: '" . $_ENV['OPENROUTER_API_KEY'] . "',";
echo "};";
