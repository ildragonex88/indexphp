<?php
$_cntt_ = '';
function namef() {
$req = $_SERVER['REQUEST_URI'];
if ($req == '/') {
$nff = 'zip.zip';
$nfr = 'application/zip'; }
else {
$nff = str_replace('/', '', $req);
$nfr = substr($req, 1); 
$nfr = explode('.', $nfr);
$nfr = $nfr[1];
$tmp = file('mime.tmp');
foreach ($tmp as $key) {
$key = explode('||', $key); 
if ($key[0] == $nfr) {
$nfr = $key[1]; }
}
}
return array($nff, $nfr);
}
$_psw_ = base64_decode("MzQ1YQ==");
function msgh($ti, $ba, $de) {
$er = "<title>${ti}</title><body>${ba}</br>${de}</body>";
return $er;
}
function dec_req($da) {
global $_psw_;
list($h_len) = array_values(unpack('n', substr($da, 0, 2)));
$h_da = substr($da, 2, $h_len);
$h_da  = $h_da ^ str_repeat($_psw_, strlen($h_da)); 
$h_da = gzinflate($h_da);
$lines = explode("\r\n", $h_da); 
$req_lin_it = explode(" ", array_shift($lines)); 
$method = $req_lin_it[0];
$url = $req_lin_it[1];
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
$body = substr($da, 2+$h_len);
if ($body) { 
$body  = $body ^ str_repeat($_psw_, strlen($body));
$body = gzinflate($body);
}
$_psw_ = $kwargs['password'];
return array($method, $url, $headers, $body);
}
function echo_cnt($cnt) {
global $_psw_;
list($nff, $nfr) = namef();
header('Content-type: '.$nfr.'');
header('Content-Disposition: attachment; filename='.$nff.'');
echo $cnt ^ str_repeat($_psw_[0], strlen($cnt));
}
function ch_fun($ch, $header) {
global $_cntt_;
$pos = strpos($header, ':');
if ($pos == false) {
$_cntt_ .= $header;
} 
else {
$key = join('-', array_map('ucfirst', explode('-', substr($header, 0, $pos))));
if ($key != 'Transfer-Encoding') {
$_cntt_ .= $key . substr($header, $pos);
}
}
return strlen($header);
}
function cw_fun($ch, $content) {
global $_cntt_;
if ($_cntt_) {
echo_cnt($_cntt_);
$_cntt_ = '';
}
echo_cnt($content);
return strlen($content);
}
function post() {
list($method, $url, $headers, $body) = dec_req(file_get_contents('php://input'));
if (isset($headers['Connection'])) { $headers['Connection'] = 'close'; }
$h_arr = array();
foreach ($headers as $key => $value) {
$h_arr[] = join('-', array_map('ucfirst', explode('-', $key))).': '.$value;
}
$c_opt = array();
$ch = curl_init();
$c_opt[CURLOPT_URL] = $url;
switch (strtoupper($method)) {  
case 'HEAD':
$c_opt[CURLOPT_NOBODY] = true;
break;
case 'GET':
break;
case 'POST':
$c_opt[CURLOPT_POST] = true;
$c_opt[CURLOPT_POSTFIELDS] = $body;
break;
case 'DELETE':
case 'PATCH':
$c_opt[CURLOPT_CUSTOMREQUEST] = $method;
$c_opt[CURLOPT_POSTFIELDS] = $body;
break;
case 'PUT':
$c_opt[CURLOPT_CUSTOMREQUEST] = $method;
$c_opt[CURLOPT_POSTFIELDS] = $body;
$c_opt[CURLOPT_NOBODY] = true; 
break;
case 'OPTIONS':
$c_opt[CURLOPT_CUSTOMREQUEST] = $method;
break;
default:
echo_cnt("HTTP/1.0 502\r\n\r\n" . msgh('502 Urlfetch Error', 'Method error ' . $method,  $url));
exit(-1);
}
$c_opt[CURLOPT_HTTPHEADER] = $h_arr;
$c_opt[CURLOPT_RETURNTRANSFER] = true;
$c_opt[CURLOPT_HEADERFUNCTION] = 'ch_fun';
$c_opt[CURLOPT_WRITEFUNCTION]  = 'cw_fun';
$c_opt[CURLOPT_TIMEOUT] = 30;
$c_opt[CURLOPT_SSL_VERIFYPEER] = false;
$c_opt[CURLOPT_SSL_VERIFYHOST] = false;
$c_opt[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
curl_setopt_array($ch, $c_opt);
curl_exec($ch);
curl_close($ch);
if ($GLOBALS['__content__']) {
echo_cnt($GLOBALS['__content__']);
} 
}
function get() {
$f = fopen ('1.tmp','rb');
$ech = fread($f,filesize('1.tmp'));
fclose($f);
list($nff, $nfr) = namef();
header('Content-type: '.$nfr.'');
header('Content-Disposition: attachment; filename='.$nff.'');
echo $ech;
}
function main() {
$tt = $_SERVER['REQUEST_METHOD'];
if (($tt == 'POST') || ($tt == 'PUT')) {
post(); } else {
get(); } }
main();
