<?php
// Para logout envíe: https://pollos.com/ldap.php?logout
if(isset($_GET['logout'])) {
    header('HTTP/1.1 401 Unauthorized');
    die('Logged out. <a href="./ldap.php">Reintente</a>');
}
// Si el servidor no recibe credenciales las pide
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="LDAP Authentication"');
    header('HTTP/1.0 401 Unauthorized');
    die('Ingrese un usuario y password válidos');
}

$usr = $_SERVER['PHP_AUTH_USER'];
$pwd = $_SERVER['PHP_AUTH_PW'];
// Verifica si es "factible" conectarse (NO se conecta)
$ldapconn = ldap_connect("ldaps://pollos.com");
if(!$ldapconn) {
    header('HTTP/1.0 400 Bad Request');
    die("No es posible conectarse al servidor");
}
// Dependerá del servidor pero en general hay que poner la versión 3 o no habrá conexión
ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, 3);
// Bind con un usuario conocido (admin generalmente) para poder buscar el dn del usuario cliente
$ldapbind = ldap_bind($ldapconn, "cn=admin,dc=pollos,dc=com", "<password_admin>");
if(!$ldapbind) {
    header('HTTP/1.0 400 Bad Request');
    die("LDAP bind fallido XXX");
}
// Búsqueda de la información del usuario cliente por cn
$result = ldap_search($ldapconn, "dc=pollos,dc=com", "(cn=$usr)");
if(!$result) {
    header('HTTP/1.0 400 Bad Request');
    die ("Error en la query: ".ldap_error($ldapconn));
}
// Obtiene un arreglo con los resultados
$entries = ldap_get_entries($ldapconn, $result);
// Verifica que haya un y solo un usuario con el cn indicado
if($entries["count"] < 1) {
    header('HTTP/1.0 401 Unauthorized');
	die("No existe el usuario indicado XXX");
}
if($entries["count"] > 1) {
    header('HTTP/1.0 401 Unauthorized');
    die("El usuario no es único XXX");
}
// Autenticación (bind) con las credenciales del usuario cliente
$ldapbind = ldap_bind($ldapconn, $entries[0]["dn"], $pwd);
if(!$ldapbind) {
    header('HTTP/1.0 401 Unauthorized');
    die("Malas credenciales XXX");
}
// Si pasamos el usuario cliente está autenticado
print($entries[0]["dn"]." Autenticado!");
print("<br>Ahora puede autorizar ;)");
print('<br><a href="./ldap.php?logout">Logout</a>');
?>
