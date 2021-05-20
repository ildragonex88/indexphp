<?php
$__content__ = '';
$__content_type__ = 'application/zip';
$__password__ = base64_decode("MzQ1YQ==");
function message_html($title, $banner, $detail) {
$error = "<meta http-equiv='content-type' content='text/html;charset=utf-8'>
<title>${title}</title>
<H1>${banner}</H1></br>
${detail}";
return $error;
}
function decode_request($data) {
global $__password__;
list($headers_length) = array_values(unpack('n', substr($data, 0, 2)));
$headers_data = substr($data, 2, $headers_length);
$headers_data  = $headers_data ^ str_repeat($__password__, strlen($headers_data)); //
$headers_data = gzinflate($headers_data);
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
$body  = $body ^ str_repeat($__password__, strlen($body));
$body = gzinflate($body);
$headers['Content-Length'] = strval(strlen($body));
unset($headers['Content-Encoding']);
}
}
return array($method, $url, $headers, $kwargs, $body);
}
function echo_content($content) {
global $__password__;
echo $content ^ str_repeat($__password__[0], strlen($content));
}
function curl_header_function($ch, $header) {
global $__content__, $__content_type__;
$pos = strpos($header, ':');
if ($pos == false) {
$__content__ .= $header;
} 
else {
$key = join('-', array_map('ucfirst', explode('-', substr($header, 0, $pos))));
if ($key != 'Transfer-Encoding') {
$__content__ .= $key . substr($header, $pos);
}
}
if (preg_match('@^Content-Type: ?(audio/|image/|video/|application/octet-stream)@i', $header)) {
//$__content_type__ = 'application/x-msdownload';
$__content_type__ = 'application/vnd.microsoft.portable-executable';
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
list($method, $url, $headers, $kwargs, $body) = decode_request(file_get_contents('php://input'));
if ($body) {
$headers['Content-Length'] = strval(strlen($body));
}
//if (isset($headers['Connection'])) {
//$headers['Connection'] = 'close';
//}
$header_array = array();
foreach ($headers as $key => $value) {
$header_array[] = join('-', array_map('ucfirst', explode('-', $key))).': '.$value;
}
$curl_opt = array();
$ch = curl_init();
$curl_opt[CURLOPT_URL] = $url;
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
case 'DELETE':
case 'PATCH':
$curl_opt[CURLOPT_CUSTOMREQUEST] = $method;
$curl_opt[CURLOPT_POSTFIELDS] = $body;
break;
case 'PUT':
$curl_opt[CURLOPT_CUSTOMREQUEST] = $method;
$curl_opt[CURLOPT_POSTFIELDS] = $body;
$curl_opt[CURLOPT_NOBODY] = true; 
break;
case 'OPTIONS':
$curl_opt[CURLOPT_CUSTOMREQUEST] = $method;
break;
default:
echo_content("HTTP/1.0 502\r\n\r\n" . message_html('502 Urlfetch Error', 'Invalid Method: ' . $method,  $url));
exit(-1);
}
$curl_opt[CURLOPT_HTTPHEADER] = $header_array;
$curl_opt[CURLOPT_RETURNTRANSFER] = true;
$curl_opt[CURLOPT_BINARYTRANSFER] = true;
$curl_opt[CURLOPT_HEADER] = false;
$curl_opt[CURLOPT_HEADERFUNCTION] = 'curl_header_function';
$curl_opt[CURLOPT_WRITEFUNCTION]  = 'curl_write_function';
$curl_opt[CURLOPT_FAILONERROR] = false;
$curl_opt[CURLOPT_FOLLOWLOCATION] = false;
$curl_opt[CURLOPT_TIMEOUT] = 30;
$curl_opt[CURLOPT_SSL_VERIFYPEER] = false;
$curl_opt[CURLOPT_SSL_VERIFYHOST] = false;
$curl_opt[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
curl_setopt_array($ch, $curl_opt);
curl_exec($ch);
if ($GLOBALS['__content__']) {
echo_content($GLOBALS['__content__']);
} 
curl_close($ch);
}
function get() {
echo "Быстрый сжиматель 88888 </br>
<form enctype='multipart/form-data' action='indexx.php' method='GET'>
<input type='hidden' name='MAX_FILE_SIZE' value='300000' />
<input name='userfile' type='file' />
 <label for='pwd'>Password:</label>
<input type='password' id='pwd' name='pwd'> 
<input type='submit' name='submit' value='Ћтправить файл' />
</form>";
exit;
}
function main() {
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
post(); } else {
get(); } }
main();
