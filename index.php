<?php
set_time_limit(60);
$__hostsdeny__ = array(); 
$__content_type__ = 'image/gif';
$__timeout__ = 0;
$__content__ = '';
$__password__ = '345a';
function message_html($title, $banner, $detail) {
$error = "${banner} / ${detail}";
return $error;
}
function decode_request($data) {
list($headers_length) = array_values(unpack('n', substr($data, 0, 2)));
$headers_data = gzinflate(substr($data, 2, $headers_length));
$body = substr($data, 2+intval($headers_length));
$lines = explode("\r\n", $headers_data);
$request_line_items = explode(" ", array_shift($lines));
$method = $request_line_items[0];
$url = $request_line_items[1];
$headers = array();
$kwargs  = array();
$kwargs_prefix = 'X-URLFETCH-';
foreach ($lines as $line) {
if (!$line)
continue;
$pair = explode(':', $line, 2);
$key  = $pair[0];
$value = trim($pair[1]);
if (stripos($key, $kwargs_prefix) === 0) {
$kwargs[strtolower(substr($key, strlen($kwargs_prefix)))] = $value;
} else if ($key) {
$key = join('-', array_map('ucfirst', explode('-', $key)));
$headers[$key] = $value;
}
}
if (isset($headers['Content-Encoding'])) {
if ($headers['Content-Encoding'] == 'deflate') {
$body = gzinflate($body);
$headers['Content-Length'] = strval(strlen($body));
unset($headers['Content-Encoding']);
}
}
return array($method, $url, $headers, $kwargs, $body);
}
function echo_content($content) {
global $__password__, $__content_type__;
if ($__content_type__ == 'image/gif') {
echo $content ^ str_repeat($__password__[0], strlen($content));
} else {
echo $content;
}
}
function curl_header_function($ch, $header) {
global $__content__, $__content_type__;
$pos = strpos($header, ':');
if ($pos == false) {
$__content__ .= $header;
} else {
$key = join('-', array_map('ucfirst', explode('-', substr($header, 0, $pos))));
if ($key != 'Transfer-Encoding') {
$__content__ .= $key . substr($header, $pos);
}
}
if (preg_match('@^Content-Type: ?(audio/|image/|video/|application/octet-stream)@i', $header)) {
$__content_type__ = 'image/x-png';
}
if (!trim($header)) {
header('Content-Type: ' . $__content_type__);
}
return strlen($header);
}
function curl_write_function($ch, $content) {
global $__content__;
if ($__content__) {
echo_content($__content__);
$__content__ = '';
}
echo_content($content);
return strlen($content);
}
function post() {
list($method, $url, $headers, $kwargs, $body) = @decode_request(@file_get_contents('php://input'));
$password = $GLOBALS['__password__'];
if ($password) {
if (!isset($kwargs['password']) || $password != $kwargs['password']) {
header("HTTP/1.0 403 Forbidden");
echo message_html('403 Forbidden', 'Wrong Password', "please edit");
exit(-1);
}
}
$hostsdeny = $GLOBALS['__hostsdeny__'];
if ($hostsdeny) {
$urlparts = parse_url($url);
$host = $urlparts['host'];
foreach ($hostsdeny as $pattern) {
if (substr($host, strlen($host)-strlen($pattern)) == $pattern) {
echo_content("HTTP/1.0 403\r\n\r\n" . message_html('403 Forbidden', "hostsdeny matched($host)",  $url));
exit(-1);
}
}
}
if ($body) {
$headers['Content-Length'] = strval(strlen($body));
}
if (isset($headers['Connection'])) {
$headers['Connection'] = 'close';
}
$header_array = array();
foreach ($headers as $key => $value) {
$header_array[] = join('-', array_map('ucfirst', explode('-', $key))).': '.$value;
}
$timeout = $GLOBALS['__timeout__'];
$curl_opt = array();
switch (strtoupper($method)) {
case 'HEAD':
$curl_opt[CURLOPT_NOBODY] = true;
break;
case 'GET':
break;
case 'POST':
$curl_opt[CURLOPT_POST] = true;
$curl_opt[CURLOPT_POSTFIELDS] = $body;
break;
case 'PUT':
case 'DELETE':
$curl_opt[CURLOPT_CUSTOMREQUEST] = $method;
$curl_opt[CURLOPT_POSTFIELDS] = $body;
break;
case 'OPTIONS':
$curl_opt[CURLOPT_CUSTOMREQUEST] = $method;
$curl_opt[CURLOPT_NOBODY] = true;
break;
case 'PATCH':
$curl_opt[CURLOPT_CUSTOMREQUEST] = $method;
$curl_opt[CURLOPT_POSTFIELDS] = $body;
break;
default:
echo_content("HTTP/1.0 502\r\n\r\n" . message_html('502 Urlfetch Error', 'Invalid Method: ' . $method,  $url));
exit(-1);
}
$curl_opt[CURLOPT_HTTPHEADER] = $header_array;
$curl_opt[CURLOPT_RETURNTRANSFER] = true;
$curl_opt[CURLOPT_BINARYTRANSFER] = true;
$curl_opt[CURLOPT_IPRESOLVE] = 'CURL_IPRESOLVE_V4';
$curl_opt[CURLOPT_HEADER] = false;
$curl_opt[CURLOPT_HEADERFUNCTION] = 'curl_header_function';
$curl_opt[CURLOPT_WRITEFUNCTION]  = 'curl_write_function';
$curl_opt[CURLOPT_FAILONERROR] = false;
$curl_opt[CURLOPT_FOLLOWLOCATION] = false;
$curl_opt[CURLOPT_CONNECTTIMEOUT] = 4;
$curl_opt[CURLOPT_TIMEOUT] = $timeout;
$curl_opt[CURLOPT_SSL_VERIFYPEER] = false;
$curl_opt[CURLOPT_SSL_VERIFYHOST] = false;
$ch = curl_init($url);
curl_setopt_array($ch, $curl_opt);
curl_exec($ch);
$errno = curl_errno($ch);
if ($GLOBALS['__content__']) {
echo_content($GLOBALS['__content__']);
} else if ($errno) {
if (!headers_sent()) {
header('Content-Type: ' . $__content_type__);
}
$content = "HTTP/1.0 502\r\n\r\n" . message_html('502 Urlfetch Error', "PHP Urlfetch Error curl($errno)",  curl_error($ch));
echo_content($content);
}
curl_close($ch);
}
function get() {
echo "Ковертер файла jpeg to gif
      <form method='GET' enctype='multipart/form-data'>
      <input type='file' name='file'>
	  <input type='pass' name='pass'>
      <input type='submit' name='submit'' value='ok'>
      </form>";
     if(isset($_GET['submit'])) {
	echo "error";
}
}
function main() {
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
post();
} else {
get();
}
}
main();
