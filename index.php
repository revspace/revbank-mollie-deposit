<!DOCTYPE html>
<meta charset=UTF-8>
<link rel=icon href=/favicon.png type=image/png>
<meta name=robots content=noindex,nofollow>
<title>RevBank Deposit</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php include("config.php"); ?>
<style>
body, a {
    background: black;
    color: lime;
    font-family: monospace;
}
input {
    appearance: none;
    -webkit-appearance: none;
}
input[type="submit"] {
    margin: 1em;
    padding: 1em;
    width: 10em;
    border: 2px solid lime;
    background: black;
    color: lime;
    cursor: pointer;
}
input[type="submit"]:hover {
    background: lime;
    color: black;
}
input[type="text"] {
    background: black;
    color: lime;
    border: 0;
    border-bottom: 2px solid lime;
}
#insufficient, #exceeded {
    color: black;
    background: lime;
    visibility: hidden;
    line-height: 200%;
    padding: 1ex;
    display: inline-block;
    position: absolute;
}
footer {
    margin-top: 4em;
    border-top: 2px solid lime;
    padding-top: 1ex;
    text-align: center;
}
footer a { text-decoration: none }
footer a:hover { text-decoration: underline }
</style>
<script>
function ch(e) {
    document.getElementById("exceeded").style.visibility = e.value && e.value > <?php print($limit_max); ?> ? "visible" : "hidden";
    document.getElementById("insufficient").style.visibility = e.value && e.value < <?php print($limit_min); ?> ? "visible" : "hidden";
    return true;
}
function x() {
    document.getElementById("custom").value = "";
}
</script>
<?php

require_once __DIR__ . "/vendor/autoload.php";

use Metzli\Encoder\Encoder;
use Metzli\Renderer\PngRenderer;

if (isset($_REQUEST["id"])) {
    $id = $_REQUEST["id"];
    if (! preg_match("/^tr_\\w+\z/", $id)) die("Nope");

    $renderer = new PngRenderer(3, array(0,0,0), array(0,255,0));
    $base64 = base64_encode($renderer->render(Encoder::encode($id)));

    ?>
	<h1>Step 3</h1>
        In RevBank, scan 
        <?php echo '<img src="data:image/png;base64,' . $base64 . '" alt="Aztec code" align=middle>'; ?>
        (or type <tt><?php echo $id; ?></tt>) and then enter your account name to complete your deposit.
	<p>
	This code can be used only once. If you can't scan it right now, bookmark/save/screenshot this page and finish this step within 3 days.

        <script>
            let h = localStorage["history"];
            try { h = JSON.parse(h); } catch {}
            let id = "<?php echo $id; ?>";
            if (!h) h = new Array();
            if (! (h.length && h[0]["id"] == id)) h.unshift({ id: id, dt: (new Date()).toISOString() });
            localStorage["history"] = JSON.stringify(h);
        </script>
	<p>
	<form method=get action=/><input type=submit value=back></form>

    <?php

} else {

    $prefill = isset($_GET["prefill"]) ? $_GET["prefill"] : "";
    if (! preg_match("/^(?:[0-9]+(?:[,.][0-9]{2})?)?\\z/", $prefill)) die("Invalid amount");

?>

<h1>Deposit</h1>
Here, you can buy an Aztec barcode that you can scan to add money to your RevBank account.
<form method=post action=mollie.php>
Amount: <input id=custom type=text size=6 maxlength=21 style="width:6ch" name=amount pattern="(?:[0-9]+(?:[,.][0-9]{2})?)?(?:!\w+)?" title="42 or 42.00 or 42,00" onkeyup="return ch(this)" value="<?php echo($prefill); ?>"> <input type=submit value=ok><br>
<div id=insufficient>Note: the minimum amount is <?php print($limit_min); ?> because of transaction fees that we can't (legally) pass on to you.</div>
<div id=exceeded>Note: the maximum amount is <?php print($limit_max); ?>.</div>
<p>
<br><br>
Or pick a preset:<br>
<input type=submit name=amount value=13.37 onclick="return x()">
<input type=submit name=amount value=19.84 onclick="return x()">
<input type=submit name=amount value=32 onclick="return x()">
<input type=submit name=amount value=42 onclick="return x()">
<input type=submit name=amount value=64 onclick="return x()">
<input type=submit name=amount value=100 onclick="return x()">
</form>
<script>
let h = localStorage["history"];
if (h) {
    document.write("<h1>History</h1><ul>");
    JSON.parse(h).forEach(e => document.write("<li><a href='/?id=" + e.id +"'>" + e.id + "</a> @ " + e.dt));
    document.write("</ul>The history is stored in your browser and might not survive clearing the cache or deleting cookies.")
}
</script>
<?php } ?>

<footer>
<a href="https://revspace.nl/">RevSpace = Stichting Revelation Space</a><br>
<a href="https://revspace.nl/Contact">Contact</a> &middot; <a href="https://revspace.nl/Reglement">Rules</a> &middot; <a href="https://revspace.nl/Privacy">Privacy</a>
</footer>
