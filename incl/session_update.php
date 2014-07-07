<?

//First of all we prevent browsers from caching the image
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

if (strpos($_SERVER['HTTP_HOST'],'connect')===0) session_name('connect');
session_start();
//This piece of code returns a blank gif
if($_GET[img] > 0){
    header('Content-Type: image/gif');
    header("Content-Disposition: inline; filename=".time().".gif");
    echo base64_decode(str_replace("\n","","
R0lGODlhAQABAPcAAAAAAAAAQAAAgAAA/
wAgAAAgQAAggAAg/wBAAABAQABAgABA/
wBgAABgQABggABg/wCAAACAQACAgACA/
wCgAACgQACggACg/wDAAADAQADAgADA/
wD/AAD/QAD/gAD//yAAACAAQCAAgCAA/
yAgACAgQCAggCAg/yBAACBAQCBAgCBA/
yBgACBgQCBggCBg/yCAACCAQCCAgCCA/
yCgACCgQCCggCCg/yDAACDAQCDAgCDA/
yD/ACD/QCD/gCD//0AAAEAAQEAAgEAA/
0AgAEAgQEAggEAg/0BAAEBAQEBAgEBA/
0BgAEBgQEBggEBg/0CAAECAQECAgECA/
0CgAECgQECggECg/0DAAEDAQEDAgEDA/
0D/AED/QED/gED//2AAAGAAQGAAgGAA/
2AgAGAgQGAggGAg/2BAAGBAQGBAgGBA/
2BgAGBgQGBggGBg/2CAAGCAQGCAgGCA/
2CgAGCgQGCggGCg/2DAAGDAQGDAgGDA/
2D/AGD/QGD/gGD//4AAAIAAQIAAgIAA/
4AgAIAgQIAggIAg/4BAAIBAQIBAgIBA/
4BgAIBgQIBggIBg/4CAAICAQICAgICA/
4CgAICgQICggICg/4DAAIDAQIDAgIDA/
4D/AID/QID/gID//6AAAKAAQKAAgKAA/
6AgAKAgQKAggKAg/6BAAKBAQKBAgKBA/
6BgAKBgQKBggKBg/6CAAKCAQKCAgKCA/
6CgAKCgQKCggKCg/6DAAKDAQKDAgKDA/
6D/AKD/QKD/gKD//8AAAMAAQMAAgMAA/
8AgAMAgQMAggMAg/8BAAMBAQMBAgMBA/
8BgAMBgQMBggMBg/8CAAMCAQMCAgMCA/
8CgAMCgQMCggMCg/8DAAMDAQMDAgMDA/
8D/AMD/QMD/gMD///8AAP8AQP8AgP8A/
/8gAP8gQP8ggP8g//9AAP9AQP9AgP9A/
/9gAP9gQP9ggP9g//+AAP+AQP+AgP+A/
/+gAP+gQP+ggP+g///AAP/AQP/AgP/A/
///AP//QP//gP///yH5BAEAAP8ALAAAA
AABAAEAAAgEAP8FBAA7"));
    exit;
}

?>
