<?php
####  SET CONSTANTS

$version = "v0.1.2";
$date = "2019-01-16";

####  END CONSTANTS

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$refresh = false;
session_start();

$files = array_slice(scandir('database/'), 2);
$users = arraystartswith($files, "u");
for ($i = 0; $i < count($users); $i++) {
	$users[$i] = substr(substr($users[$i],1),0,-5);
}
$items = arraystartswith($files, "i");
for ($i = 0; $i < count($items); $i++) {
	$items[$i] = substr(substr($items[$i],1),0,-5);
}

if (isset($_GET["checkin"]) && file_exists('database/i' . $_GET["item"] . '.json')) {
	checkin($_GET["item"]);
	$_SESSION["success"] = "Item ".$_GET["item"]." checked in";
	header("Location:?item=".$_GET["item"]);
	eof();
}
if (isset($_GET["checkin"]) && !file_exists('database/i' . $_GET["item"] . '.json')) {
	header("Location:?item=" . $_GET["item"]);
	eof();
}
if (isset($_GET["checkout"]) && file_exists('database/i' . $_GET["item"] . '.json') && file_exists('database/u' . $_GET["checkout"] . '.json')) {
	checkout($_GET["checkout"]);
	$_SESSION["success"] = "Item ".$_GET["item"]." checked out to user \"".$_GET["checkout"]."\"";
	header("Location:?item=".$_GET["item"]);
	eof();
}
if (isset($_GET["checkout"]) && $_GET["checkout"] == '') {
	$_SESSION["error"] = "Username can't be empty";
	header("Location:?item=" . $_GET["item"]);
	eof();
}
if (isset($_GET["checkout"]) && !file_exists('database/u' . $_GET["checkout"] . '.json')) {
	$_SESSION["error"] = "User \"" . $_GET["checkout"] . "\" doesn't exsist";
	header("Location:.");
	eof();
}
if (isset($_GET["query"]) && $_GET["query"] != '') {
	$query = $_GET["query"];
}
if (isset($_GET["query"]) && $_GET["query"] == '') {
	$query = "all";
}
if (isset($_POST["general_make"]) && !isset($_POST["item"])) {
	createitem(true);
	eof();
}
if (isset($_POST["general_make"]) && isset($_POST["item"])) {
	createitem(false);
	eof();
}

function createitem($create){
	$item["general"]["make"] = $_POST["general_make"];
	$item["general"]["line"] = $_POST["general_line"];
	$item["general"]["model"] = $_POST["general_model"];
	$item["general"]["serial"] = $_POST["general_serial"];
	$item["general"]["cat"] = $_POST["general_cat"];
	$item["general"]["subcat"] = $_POST["general_subcat"];
	$item["general"]["dom"] = $_POST["general_dom"];
	$item["display"]["tech"] = $_POST["display_tech"];
	$item["display"]["screen_size"] = $_POST["display_screen_size"];
	$item["display"]["pixel_size"] = $_POST["display_pixel_size"];
	$item["display"]["aspect"] = $_POST["display_aspect"];
	$item["display"]["touch"] = $_POST["display_touch"];
	$item["keyboard"]["tech"] = $_POST["keyboard_tech"];
	$item["keyboard"]["layout"] = $_POST["keyboard_layout"];
	$item["keyboard"]["backlight"] = $_POST["keyboard_backlight"];
	$item["mouse"]["tech"] = $_POST["mouse_tech"];
	$item["mouse"]["buttons"] = $_POST["mouse_buttons"];
	$item["mouse"]["scroll"] = $_POST["mouse_scroll"];
	$item["io"]["display"] = $_POST["io_display"];
	$item["io"]["networking"] = $_POST["io_net"];
	$item["io"]["usb"] = $_POST["io_usb"];
	$item["io"]["optical_drive"] = $_POST["io_optic"];
	$item["io"]["sound"] = $_POST["io_sound"];
	$item["io"]["power"] = $_POST["io_power"];
	$item["io"]["edge"] = $_POST["io_edge"];
	$item["io"]["other"] = $_POST["io_other"];
	$item["compute"]["cpu"] = $_POST["compute_cpu"];
	$item["compute"]["cpu_speed"] = $_POST["compute_cpuspeed"];
	$item["compute"]["cores"] = $_POST["compute_cores"];
	$item["compute"]["ram"] = $_POST["compute_ram"];
	$item["compute"]["gpu"] = $_POST["compute_gpu"];
	$item["compute"]["gpu_speed"] = $_POST["compute_gpuspeed"];
	$item["compute"]["vram"] = $_POST["compute_vram"];
	$item["compute"]["ogl"] = $_POST["compute_ogl"];
	$item["compute"]["hdd"] = $_POST["compute_hdd"];
	$item["compute"]["hdd_speed"] = $_POST["compute_hddspeed"];
	$item["battery"]["tech"] = $_POST["battery_tech"];
	$item["battery"]["size"] = $_POST["battery_size"];
	$item["battery"]["cells"] = $_POST["battery_cells"];
	$item["notes"] = $_POST["notes"];
	if ($create) {
		file_put_contents("database/i" . nextitemname(1100) . ".json", json_encode($item));
		$_SESSION["success"] = "Item ".nextitemname(1100)." created";
		header("Location:?item=".nextitemname(1100));
		eof();
	} else {
		file_put_contents("database/i" . $_GET["item"] . ".json", json_encode($item));
		$_SESSION["success"] = "Item " . $_GET["item"] . " edited";
		header("Location:?item=".$_GET["item"]);
		eof();
	}
}

function nextitemname($i){
	global $items;
	if (iselementinarray($items, $i) !== false) {
		return nextitemname($i + 1);
	} else {
		return $i;
	}
}

function iselementinarray($array, $element){
	for ($i = 0; $i < count($array); $i++) {
		if ($array[$i] == $element) {
			return $i;
		}
	}
	return false;
}

function checkin($i) {
	if (isitemout($i)) {
		$user = json_decode(file_get_contents("database/u" . whoitemout($i) . ".json"), true);
		array_splice($user["items_out"], whereitemout(whoitemout($i), $i), 1);
//  CHECKOUT		$user["items_out"][count($user["items_out"])] = $i;
		file_put_contents("database/u" . whoitemout($i) . ".json", json_encode($user, true));
	} else {
		header("Location:?item=" . $_GET["item"] . "&error=Item+" . $i . "+is+not+out");
		eof();
	}
}
function checkout($i) {
	if (!isitemout($i)) {
		$user = json_decode(file_get_contents("database/u" . $_GET["checkout"] . ".json"), true);
		array_push($user["items_out"], $_GET["item"]);
		file_put_contents("database/u" . $_GET["checkout"] . ".json", json_encode($user, true));
	} else {
		header("Location:?item=" . $_GET["item"] . "&error=Item+" . $i . "+is+already+out");
		eof();
	}
}
function isitemout($i) {
	global $users;
	for ($j = 0; $j < count($users); $j++) {
		$user = json_decode(file_get_contents("database/u" . $users[$j] . ".json"), true);
		echo json_encode($user);
		for ($k = 0; $k < count($user["items_out"]); $k++) {
			if ($user["items_out"][$k] == $i) {
				return true;
			}
		}
	}
	return false;
}
function whoitemout($i) {
	global $users;
	for ($j = 0; $j < count($users); $j++) {
		$user = json_decode(file_get_contents("database/u" . $users[$j] . ".json"), true);
		for ($k = 0; $k < count($user["items_out"]); $k++) {
			if ($user["items_out"][$k] == $i) {
				return $users[$j];
			}
		}
	}
	return false;
}
function whereitemout($i, $j) {
	$user = json_decode(file_get_contents("database/u" . $i . ".json"), true);
	for ($k = 0; $k < count($user["items_out"]); $k++) {
		if ($user["items_out"][$k] == $j) {
			return $k;
		}
	}
	return false;
}

function itemsout($all) {
	if ($all) {
		$rows = "";
    global $users;
    for ($i = 0; $i < count($users); $i++) {
      $user = json_decode(file_get_contents("database/u" . $users[$i] . ".json"), true);
      for ($j = 0; $j < count($user["items_out"]); $j++) {
        $item = json_decode(file_get_contents("database/i" . $user["items_out"][$j] . ".json"), true);
        $rows .= '<tr class="headtwo">';
        $rows .= '<td><a href="?item=' . $user["items_out"][$j] . '">' . $user["items_out"][$j] . '</td>';
        $rows .= '<td>' . $users[$i] . '</td>';
        $rows .= '<td>' . ucfirst($item["general"]["make"]) . '</td>';
        $rows .= '<td>' . ucfirst($item["general"]["model"]) . '</td>';
//        $rows .= '<td>' . ucfirst($item["general"]["cat"]) . '</td>';
        $rows .= "</tr>\n";
      }
	  }
		$row = explode("\n", $rows);
		sort($row);
		if (isset($_GET["more"])) {
			if ($_GET["more"] == "out") {
				$rows = implode("\n", $row);
				if (count($row) > 9) {
					$rows .= '<tr><th colspan="5" style="text-align:right;padding-right:50px;"><a href=".">Less</a></th></tr>';
				}
			} else {
				$rows = implode("\n", array_slice($row, 0, 9));
				if (count($row) > 9) {
					$rows .= '<tr><th colspan="5" style="text-align:right;padding-right:50px;"><a href="?more=out">More</a></th></tr>';
				}
			}
		} else {
			$rows = implode("\n", array_slice($row, 0, 9));
			if (count($row) > 9) {
				$rows .= '<tr><th colspan="5" style="text-align:right;padding-right:50px;"><a href="?more=out">More</a></th></tr>';
			}
		}
		echo '<table class="headtwo"><tr><th colspan="5" class="textsizebigger">Items Out<span class="fr">(';
		echo count($row) - 1;
		echo ')</span></th></tr><tr><th>ID</th><th>By</th><th>Make</th><th>Model</th>' . $rows; //<th>Category</th></tr>'
  } else {
		echo '<table class="headtwo"><tr><th colspan="4" class="textsizebigger">Items Out</th></tr><tr><th>Item ID</th><th>Make</th><th>Model</th><th>Type</th></tr>';
    $user = json_decode(file_get_contents("database/u" . $i . ".json"), true);
  	for ($j = 0; $j < count($user["items_out"]); $j++) {
  		$item = json_decode(file_get_contents("database/i" . $user["items_out"][$j] . ".json"), true);
  		echo '<tr class="headtwo">';
  		echo '<td><a href="?item=' . $user["items_out"][$j] . '">' . $user["items_out"][$j] . '</td>';
  		echo '<td>' . ucfirst($item["general"]["make"]) . '</td>';
  		echo '<td>' . ucfirst($item["general"]["model"]) . '</td>';
	  	echo '<td>' . ucfirst($item["general"]["cat"]) . '</td>';
	  	echo '</tr>';
	  }
  }
	echo '</table>';
}

function users(){
	global $users;
	$rows = "";
   for ($i = 0; $i < count($users); $i++) {
     $user = json_decode(file_get_contents("database/u" . $users[$i] . ".json"), true);
     $rows .= '<tr class="headtwo">';
     $rows .= '<td><!--<a href="?user=' . $users[$i] . '">-->' . $users[$i] . '<!--</a>--></td>';
     $rows .= '<td>' . $user["name"] . '</td>';
     $rows .= '<td style="text-align:center">' . count($user["items_out"]) . '</td>';
     $rows .= "</tr>\n";
  }
	$row = explode("\n", $rows);
	if (isset($_GET["more"])) {
		if ($_GET["more"] == "users") {
			$rows = implode("\n", $row);
			if (count($row) > 8) {
				$rows .= '<tr><th colspan="3" style="text-align:right;padding-right:50px;"><a href=".">Less</a></th></tr>';
			}
		} else {
			$rows = implode("\n", array_slice($row, 0, 9));
			if (count($row) > 8) {
				$rows .= '<tr><th colspan="3" style="text-align:right;padding-right:50px;"><a href="?more=users">More</a></th></tr>';
			}
		}
	} else {
		$rows = implode("\n", array_slice($row, 0, 9));
		if (count($row) > 8) {
			$rows .= '<tr><th colspan="3" style="text-align:right;padding-right:50px;"><a href="?more=out">users</a></th></tr>';
		}
	}
	echo '<table class="headtwo"><tr><th colspan="5" class="textsizebigger">Users<span class="fr">(';
	echo count($row) -1;
	echo ')</span></th></tr><tr><th>ID</th><th>Real Name</th><th>Total</th></tr>' . $rows . "</table>";
}

function cats(){
	global $items;
	$cats = [];
	for ($i = 0; $i < count($items); $i++){
		$item = json_decode(file_get_contents("database/i" . $items[$i] . ".json"), true);
		if (isset($cats[ucfirst($item["general"]["cat"])])) {
			$cats[ucfirst($item["general"]["cat"])]++;
		} else {
			$cats[ucfirst($item["general"]["cat"])] = 1;
		}
	}
	$rows = "";
	$keys = array_keys($cats);
	sort($keys);
	for ($i = 0; $i < count($cats); $i++) {
		$rows .= '<tr class="headtwo"><td><!--<a href="?cat=' . lcfirst($keys[$i]) . '">-->' . $keys[$i] . '<!--</a>--></td><td style="text-align:center">' . $cats[$keys[$i]] . "</td></tr>\n";
	}
	$row = explode("\n", $rows);
	if (isset($_GET["more"])) {
		if ($_GET["more"] == "cats") {
			$rows = implode("\n", $row);
			if (count($row) > 8) {
				$rows .= '<tr><th colspan="2" style="text-align:right;padding-right:50px;"><a href=".">Less</a></th></tr>';
			}
		} else {
			$rows = implode("\n", array_slice($row, 0, 8));
			if (count($row) > 8) {
				$rows .= '<tr><th colspan="2" style="text-align:right;padding-right:50px;"><a href="?more=cats">More</a></th></tr>';
			}
		}
	} else {
		$rows = implode("\n", array_slice($row, 0, 8));
		if (count($row) > 8) {
			$rows .= '<tr><th colspan="2" style="text-align:right;padding-right:50px;"><a href="?more=cats">More</a></th></tr>';
		}
	}
	echo '<table class="headtwo"><tr><th colspan="5" class="textsizebigger">Categories<span class="fr">(';
	echo count($cats);
	echo ')</span></th></tr><tr><th>Name</th><th>Total</th></tr>' . $rows . "</table>";
}

function makes(){
	global $items;
	$cats = [];
	for ($i = 0; $i < count($items); $i++){
		$item = json_decode(file_get_contents("database/i" . $items[$i] . ".json"), true);
		if ($item["general"]["make"] == '') {
			$item["general"]["make"] = "<i>unknown</i>";
		}
		if (isset($cats[ucfirst($item["general"]["make"])])) {
			$cats[ucfirst($item["general"]["make"])]++;
		} else {
			$cats[ucfirst($item["general"]["make"])] = 1;
		}
	}
	$rows = "";
	$keys = array_keys($cats);
	sort($keys);
	for ($i = 0; $i < count($cats); $i++) {
		$rows .= '<tr class="headtwo"><td><!--<a href="?make=' . $keys[$i] . '">-->' . $keys[$i] . '<!--</a>--></td><td style="text-align:center">' . $cats[$keys[$i]] . "</td></tr>\n";
	}
	$row = explode("\n", $rows);
	if (isset($_GET["more"])) {
		if ($_GET["more"] == "makes") {
			$rows = implode("\n", $row);
			if (count($row) > 8) {
				$rows .= '<tr><th colspan="2" style="text-align:right;padding-right:50px;"><a href=".">Less</a></th></tr>';
			}
		} else {
			$rows = implode("\n", array_slice($row, 0, 8));
			if (count($row) > 8) {
				$rows .= '<tr><th colspan="2" style="text-align:right;padding-right:50px;"><a href="?more=makes">More</a></th></tr>';
			}
		}
	} else {
		$rows = implode("\n", array_slice($row, 0, 8));
		if (count($row) > 8) {
			$rows .= '<tr><th colspan="2" style="text-align:right;padding-right:50px;"><a href="?more=makes">More</a></th></tr>';
		}
	}
	echo '<table class="headtwo"><tr><th colspan="5" class="textsizebigger">Makes<span class="fr">(';
	echo count($cats);
	echo ')</span></th></tr><tr><th>Name</th><th>Total</th></tr>' . $rows . "</table>";
}

function create(){
	if (isset($_GET["create"])) {
		echo '<form method="post">';
		echo '<table><tr><th colspan="2" class="textsizebigger">General</th></tr>';
		echo '<tr class="headone"><td>Make</td><td><input name="general_make" placeholder="Acer" autofocus/></td></tr>';
		echo '<tr class="headone"><td>Line</td><td><input name="general_line" placeholder="Aspire One"/></td></tr>';
		echo '<tr class="headone"><td>Model №</td><td><input name="general_model" placeholder="AOD255"/></td></tr>';
		echo '<tr class="headone"><td>Serial №</td><td><input name="general_serial" placeholder="LUSDQ0D0740425940C1601"/></td></tr>';
		echo '<tr class="headone"><td>Category</td><td><input name="general_cat" placeholder="Clamshell" required/></td></tr>';
		echo '<tr class="headone"><td>Sub-Category</td><td><input name="general_subcat" placeholder="Netbook"/></td></tr>';
		echo '<tr class="headone"><td>Date Made</td><td><input name="general_dom" placeholder="<i>for Windows 7 Starter</i>"/></td></tr>';
		echo '</table>';

		echo '<table><tr><th colspan="2" class="textsizebigger">Display</th></tr>';
		echo '<tr class="headone"><td>Tech</td><td><input name="display_tech" placeholder="LED LCD"/></td></tr>';
		echo '<tr class="headone"><td>Diagnal Size</td><td><input name="display_screen_size" placeholder=\'10.1"\'/></td></tr>';
		echo '<tr class="headone"><td>Resolution</td><td><input name="display_pixel_size" placeholder="800x600"/></td></tr>';
		echo '<tr class="headone"><td>Aspect Ratio</td><td><input name="display_aspect" placeholder="16:9"/></td></tr>';
		echo '<tr class="headone"><td>Touch</td><td><input name="display_touch" placeholder="No"/></td></tr>';
		echo '</table>';

		echo '<table><tr><th colspan="2" class="textsizebigger">Keyboard</th></tr>';
		echo '<tr class="headone"><td>Tech</td><td><input name="keyboard_tech" placeholder="Rubber Dome"/></td></tr>';
		echo '<tr class="headone"><td>Layout</td><td><input name="keyboard_layout" placeholder="Qwerty"/></td></tr>';
		echo '<tr class="headone"><td>Backlight</td><td><input name="keyboard_backlight" placeholder="None"/></td></tr>';
		echo '</table>';

		echo '<table><tr><th colspan="2" class="textsizebigger">Mouse</th></tr>';
		echo '<tr class="headone"><td>Tech</td><td><input name="mouse_tech" placeholder="Tackpad"/></td></tr>';
		echo '<tr class="headone"><td>Buttons</td><td><input name="mouse_buttons" placeholder="2"/></td></tr>';
		echo '<tr class="headone"><td>Scroll</td><td><input name="mouse_scroll" placeholder="Vertical"/></td></tr>';
		echo '</table>';

		echo '<table><tr><th colspan="2" class="textsizebigger">Input / Output</th></tr>';
		echo '<tr class="headone"><td>Display</td><td><input name="io_display" placeholder="1x VGA"/></td></tr>';
		echo '<tr class="headone"><td>Networking</td><td><input name="io_net" placeholder="1x 10/100/1000 Ethernet<br>1x Wifi <i>unknown specifics</i>"/></td></tr>';
		echo '<tr class="headone"><td>USB</td><td><input name="io_usb" placeholder="3x USB 2.0"/></td></tr>';
		echo '<tr class="headone"><td>Optical Drive</td><td><input name="io_optic" placeholder="None"/></td></tr>';
		echo '<tr class="headone"><td>Sound</td><td><input name="io_sound" placeholder="1x 3.5mm Headphone<br>1x 3.5mm Microphone"/></td></tr>';
		echo '<tr class="headone"><td>Power</td><td><input name="io_power" placeholder="19VDC 2.15A"/></td></tr>';
		echo '<tr class="headone"><td>Edges</td><td><input name="io_edge" placeholder="3x PCI<br>1x AGP"/></td></tr>';
		echo '<tr class="headone"><td>Other</td><td><input name="io_other" placeholder="SD Card Reader"/></td></tr>';
		echo '</table>';

		echo '<table><tr><th colspan="2" class="textsizebigger">Compute</th></tr>';
		echo '<tr class="headone"><td>CPU</td><td><input name="compute_cpu" placeholder="Intel Atom 1830"/></td></tr>';
		echo '<tr class="headone"><td>CPU Speed</td><td><input name="compute_cpuspeed" placeholder="1.66 GHz"/></td></tr>';
		echo '<tr class="headone"><td>CPU Cores</td><td><input name="compute_cores" placeholder="2"/></td></tr>';
		echo '<tr class="headone"><td>RAM</td><td><input name="compute_ram" placeholder="2GB"/></td></tr>';
		echo '<tr class="headone"><td>GPU</td><td><input name="compute_gpu" placeholder="NVidia GTX 1080ti"/></td></tr>';
		echo '<tr class="headone"><td>GPU Speed</td><td><input name="compute_gpuspeed" placeholder="1666 MHz"/></td></tr>';
		echo '<tr class="headone"><td>VRAM</td><td><input name="compute_vram" placeholder="16GB"/></td></tr>';
		echo '<tr class="headone"><td>OpenGl Version</td><td><input name="compute_ogl" placeholder="1.4"/></td></tr>';
		echo '<tr class="headone"><td>HDD</td><td><input name="compute_hdd" placeholder="250GB"/></td></tr>';
		echo '<tr class="headone"><td>HDD Speed</td><td><input name="compute_hddspeed" placeholder="7400 RPM"/></td></tr>';
		echo '</table>';

		echo '<table><tr><th colspan="2" class="textsizebigger">Battery</th></tr>';
		echo '<tr class="headone"><td>Tech</td><td><input name="battery_tech" placeholder="Li-ion"/></td></tr>';
		echo '<tr class="headone"><td>Size</td><td><input name="battery_size" placeholder="25Whr"/></td></tr>';
		echo '<tr class="headone"><td>№ of Cells</td><td><input name="battery_cells" placeholder="6"/></td></tr>';
		echo '</table>';

		echo '<table><tr><th class="textsizebigger">Notes</th></tr>';
		echo '<tr class="headone"><td><input name="notes" placeholder="The screen hinge is loose"/></td></tr>';
		echo '</table>';

		echo '<button type="submit">Create Item</button>';
		echo "</form>";
	} else {
		echo '<table class="headtwo"><tr><th colspan="1" class="textsizebigger">Create</th></tr><tr><th colspan="1">&nbsp;</th></tr><tr><td style="text-align:center;padding-right:15px;"><a href="?create=item">Item</a></td><!--<td style="text-align:center"><a href="?create=user">User</a></td>--></tr></table>';
	}
}

function edit($itemid){
	$item = json_decode(file_get_contents("database/i" . $itemid . ".json"), true);

//	echo '<div style="padding:10px;margin:10px;border:2px solid #FF0;border-radius:4px;background-color:#FFD;color:#000"><b>NOTICE: Fields left blank will not be updated</b></div>';

	echo '<form method="post"><input type="hidden" value="'.$_GET["item"].'" name="item"/>';
	echo '<table><tr><th colspan="2" class="textsizebigger">General</th></tr>';
	echo '<tr class="headone"><td>Make</td><td>     <input value="'.$item["general"]["make"].'" name="general_make" autofocus/></td></tr>';
	echo '<tr class="headone"><td>Line</td><td>     <input value="'.$item["general"]["line"].'" name="general_line"/></td></tr>';
	echo '<tr class="headone"><td>Model №</td><td>  <input value="'.$item["general"]["model"].'" name="general_model"/></td></tr>';
	echo '<tr class="headone"><td>Serial №</td><td> <input value="'.$item["general"]["serial"].'" name="general_serial"/></td></tr>';
	echo '<tr class="headone"><td>Category</td><td> <input value="'.$item["general"]["cat"].'" name="general_cat" required/></td></tr>';
	echo '<tr class="headone"><td>Sub-Category</td><td> <input value="'.$item["general"]["subcat"].'" name="general_subcat"/></td></tr>';
	echo '<tr class="headone"><td>Date Made</td><td><input value="'.$item["general"]["dom"].'" name="general_dom"/></td></tr>';
	echo '</table>';

	echo '<table><tr><th colspan="2" class="textsizebigger">Display</th></tr>';
	echo '<tr class="headone"><td>Tech</td><td>        <input value="'.$item["display"]["tech"].'" name="display_tech"/></td></tr>';
	echo '<tr class="headone"><td>Diagnal Size</td><td><input value="'.$item["display"]["screen_size"].'" name="display_screen_size"/></td></tr>';
	echo '<tr class="headone"><td>Resolution</td><td>  <input value="'.$item["display"]["pixel_size"].'" name="display_pixel_size"/></td></tr>';
	echo '<tr class="headone"><td>Aspect Ratio</td><td><input value="'.$item["display"]["aspect"].'" name="display_aspect"/></td></tr>';
	echo '<tr class="headone"><td>Touch</td><td>       <input value="'.$item["display"]["touch"].'" name="display_touch"/></td></tr>';
	echo '</table>';

	echo '<table><tr><th colspan="2" class="textsizebigger">Keyboard</th></tr>';
	echo '<tr class="headone"><td>Tech</td><td>     <input value="'.$item["keyboard"]["tech"].'" name="keyboard_tech"/></td></tr>';
	echo '<tr class="headone"><td>Layout</td><td>   <input value="'.$item["keyboard"]["layout"].'" name="keyboard_layout"/></td></tr>';
	echo '<tr class="headone"><td>Backlight</td><td><input value="'.$item["keyboard"]["backlight"].'" name="keyboard_backlight"/></td></tr>';
	echo '</table>';

	echo '<table><tr><th colspan="2" class="textsizebigger">Mouse</th></tr>';
	echo '<tr class="headone"><td>Tech</td><td>   <input value="'.$item["mouse"]["tech"].'" name="mouse_tech"/></td></tr>';
	echo '<tr class="headone"><td>Buttons</td><td><input value="'.$item["mouse"]["buttons"].'" name="mouse_buttons"/></td></tr>';
	echo '<tr class="headone"><td>Scroll</td><td> <input value="'.$item["mouse"]["scroll"].'" name="mouse_scroll"/></td></tr>';
	echo '</table>';

	echo '<table><tr><th colspan="2" class="textsizebigger">Input / Output</th></tr>';
	echo '<tr class="headone"><td>Display</td><td>      <input value="'.$item["io"]["display"].'" name="io_display"/></td></tr>';
	echo '<tr class="headone"><td>Networking</td><td>   <input value="'.$item["io"]["networking"].'" name="io_net"/></td></tr>';
	echo '<tr class="headone"><td>USB</td><td>          <input value="'.$item["io"]["usb"].'" name="io_usb"/></td></tr>';
	echo '<tr class="headone"><td>Optical Drive</td><td><input value="'.$item["io"]["optical_drive"].'" name="io_optic"/></td></tr>';
	echo '<tr class="headone"><td>Sound</td><td>        <input value="'.$item["io"]["sound"].'" name="io_sound"/></td></tr>';
	echo '<tr class="headone"><td>Power</td><td>        <input value="'.$item["io"]["power"].'" name="io_power"/></td></tr>';
	echo '<tr class="headone"><td>Edges</td><td>        <input value="'.$item["io"]["edge"].'" name="io_edge"/></td></tr>';
	echo '<tr class="headone"><td>Other</td><td>        <input value="'.$item["io"]["other"].'" name="io_other"/></td></tr>';
	echo '</table>';

	echo '<table><tr><th colspan="2" class="textsizebigger">Compute</th></tr>';
	echo '<tr class="headone"><td>CPU</td><td><input value="'.$item["compute"]["cpu"].'" name="compute_cpu"/></td></tr>';
	echo '<tr class="headone"><td>CPU Speed</td><td><input value="'.$item["compute"]["cpu_speed"].'" name="compute_cpuspeed"/></td></tr>';
	echo '<tr class="headone"><td>CPU Cores</td><td><input value="'.$item["compute"]["cores"].'" name="compute_cores"/></td></tr>';
	echo '<tr class="headone"><td>RAM</td><td><input value="'.$item["compute"]["ram"].'" name="compute_ram"/></td></tr>';
	echo '<tr class="headone"><td>GPU</td><td><input value="'.$item["compute"]["gpu"].'" name="compute_gpu"/></td></tr>';
	echo '<tr class="headone"><td>GPU Speed</td><td><input value="'.$item["compute"]["gpu_speed"].'" name="compute_gpuspeed"/></td></tr>';
	echo '<tr class="headone"><td>VRAM</td><td><input value="'.$item["compute"]["vram"].'" name="compute_vram"/></td></tr>';
	echo '<tr class="headone"><td>OpenGL Version</td><td><input value="'.$item["compute"]["ogl"].'" name="compute_ogl"/></td></tr>';
	echo '<tr class="headone"><td>HDD</td><td><input value="'.$item["compute"]["hdd"].'" name="compute_hdd"/></td></tr>';
	echo '<tr class="headone"><td>HDD Speed</td><td><input value="'.$item["compute"]["hdd_speed"].'" name="compute_hddspeed"/></td></tr>';
	echo '</table>';

	echo '<table><tr><th colspan="2" class="textsizebigger">Battery</th></tr>';
	echo '<tr class="headone"><td>Tech</td><td>      <input value="'.$item["battery"]["tech"].'" name="battery_tech"/></td></tr>';
	echo '<tr class="headone"><td>Size</td><td>      <input value="'.$item["battery"]["size"].'" name="battery_size"/></td></tr>';
	echo '<tr class="headone"><td>№ of Cells</td><td><input value="'.$item["battery"]["cells"].'" name="battery_cells"/></td></tr>';
	echo '</table>';

	echo '<table><tr><th class="textsizebigger">Notes</th></tr>';
	echo '<tr class="headone"><td><input name="notes" value="'.$item["notes"].'"/></td></tr>';
	echo '</table>';

	echo '<button type="submit">Save</button>';
	echo "</form>";
}

function arraystartswith($haystack, $needle){
	$result = [];
	for ($element = 0; $element < count($haystack); $element++) {
		if (substr($haystack[$element],0,1) === $needle) {
			array_push($result, $haystack[$element]);
		}
	}
	return $result;
}

function itemdetails() {
	$item = json_decode(file_get_contents("database/i" . $_GET["item"] . ".json"), true);
	$undefined = "<i>unknown</i>";
	$undefinedimage = "<i>no image</i>";

	echo '<table><tr><th colspan="2" class="textsizebigger">General</th></tr><tr class="headone"><td>Make</td><td>';
	if (strtoupper($item["general"]["make"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["general"]["make"]!="") {
		echo $item["general"]["make"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Line</td><td>';
	if (strtoupper($item["general"]["line"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["general"]["line"]!="") {
		echo $item["general"]["line"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Model №</td><td>';
	if (strtoupper($item["general"]["model"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["general"]["model"]!="") {
		echo $item["general"]["model"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Serial №</td><td>';
	if (strtoupper($item["general"]["serial"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["general"]["serial"]!="") {
		echo $item["general"]["serial"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Category</td><td>';
	if (strtoupper($item["general"]["cat"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["general"]["cat"]!="") {
		echo $item["general"]["cat"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Sub-Category</td><td>';
	if (strtoupper($item["general"]["subcat"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["general"]["subcat"]!="") {
		echo $item["general"]["subcat"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Date Made</td><td>';
	if (strtoupper($item["general"]["dom"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["general"]["dom"]!="") {
		echo $item["general"]["dom"];
	} else {
		echo $undefined;
	}
	echo '</td></tr></table><table><tr><th colspan="2" class="textsizebigger">Display</th></tr><tr class="headone"><td>Tech</td><td>';
	if (strtoupper($item["display"]["tech"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["display"]["tech"]!="") {
		echo $item["display"]["tech"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Diagnal Size</td><td>';
	if (strtoupper($item["display"]["tech"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["display"]["screen_size"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["display"]["screen_size"]!="") {
		echo $item["display"]["screen_size"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Resolution</td><td>';
	if (strtoupper($item["display"]["tech"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["display"]["pixel_size"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["display"]["pixel_size"]!="") {
		echo $item["display"]["pixel_size"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Aspect Ratio</td><td>';
	if (strtoupper($item["display"]["tech"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["display"]["aspect"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["display"]["aspect"]!="") {
		echo $item["display"]["aspect"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Touch</td><td>';
	if (strtoupper($item["display"]["tech"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["display"]["touch"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["display"]["touch"]!="") {
		echo $item["display"]["touch"];
	} else {
		echo $undefined;
	}
	echo '</td></tr></table><table><tr><th colspan="2" class="textsizebigger">Keyboard</th></tr><tr class="headone"><td>Tech</td><td>';
	if (strtoupper($item["keyboard"]["tech"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["keyboard"]["tech"]!="") {
		echo $item["keyboard"]["tech"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Layout</td><td>';
	if (strtoupper($item["keyboard"]["tech"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["keyboard"]["layout"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["keyboard"]["layout"]!="") {
		echo $item["keyboard"]["layout"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Backlight</td><td>';
	if (strtoupper($item["keyboard"]["tech"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["keyboard"]["backlight"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["keyboard"]["backlight"]!="") {
		echo $item["keyboard"]["backlight"];
	} else {
		echo $undefined;
	}
	echo '</td></tr></table><table><tr><th colspan="2" class="textsizebigger">Mouse</th></tr><tr class="headone"><td>Tech</td><td>';
	if (strtoupper($item["mouse"]["tech"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["mouse"]["tech"]!="") {
		echo $item["mouse"]["tech"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Buttons</td><td>';
	if (strtoupper($item["mouse"]["tech"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["mouse"]["buttons"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["mouse"]["buttons"]!="") {
		echo $item["mouse"]["buttons"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Scroll</td><td>';
	if (strtoupper($item["mouse"]["tech"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["mouse"]["scroll"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["mouse"]["scroll"]!="") {
		echo $item["mouse"]["scroll"];
	} else {
		echo $undefined;
	}
	echo '</td></tr></table><table><tr><th colspan="2" class="textsizebigger">Input / Output</th></tr><tr class="headone"><td>Display</td><td>';
	if (strtoupper($item["io"]["display"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["io"]["display"]!="") {
		echo $item["io"]["display"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Networking</td><td>';
	if (strtoupper($item["io"]["networking"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["io"]["networking"]!="") {
		echo $item["io"]["networking"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>USB</td><td>';
	if (strtoupper($item["io"]["usb"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["io"]["usb"]!="") {
		echo $item["io"]["usb"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Optical Drive</td><td>';
	if (strtoupper($item["io"]["optical_drive"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["io"]["optical_drive"]!="") {
		echo $item["io"]["optical_drive"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Sound</td><td>';
	if (strtoupper($item["io"]["sound"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["io"]["sound"]!="") {
		echo $item["io"]["sound"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Power</td><td>';
	if (strtoupper($item["io"]["power"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["io"]["power"]!="") {
		echo $item["io"]["power"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Edges</td><td>';
	if (strtoupper($item["io"]["edge"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["io"]["edge"]!="") {
		echo $item["io"]["edge"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Other</td><td>';
	if (strtoupper($item["io"]["other"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["io"]["other"]!="") {
		echo $item["io"]["other"];
	} else {
		echo $undefined;
	}
	echo '</td></tr></table><table><tr><th colspan="2" class="textsizebigger">Compute</th></tr><tr class="headone"><td>CPU</td><td>';
	if (strtoupper($item["compute"]["cpu"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["compute"]["cpu"]!="") {
		echo $item["compute"]["cpu"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>CPU Speed</td><td>';
	if (strtoupper($item["compute"]["cpu"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["compute"]["cpu_speed"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["compute"]["cpu_speed"]!="") {
		echo $item["compute"]["cpu_speed"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>CPU Cores</td><td>';
	if (strtoupper($item["compute"]["cpu"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["compute"]["cores"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["compute"]["cores"]!="") {
		echo $item["compute"]["cores"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>RAM</td><td>';
	if (strtoupper($item["compute"]["ram"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["compute"]["ram"]!="") {
		echo $item["compute"]["ram"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>GPU</td><td>';
	if (strtoupper($item["compute"]["gpu"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["compute"]["gpu"]!="") {
		echo $item["compute"]["gpu"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>GPU Speed</td><td>';
	if (strtoupper($item["compute"]["gpu"])=="NONE") {
		echo "<i>n/a</i>";
	} else if ($item["compute"]["gpu_speed"]!="") {
		echo $item["compute"]["gpu_speed"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>VRAM</td><td>';
	if (strtoupper($item["compute"]["gpu"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["compute"]["vram"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["compute"]["vram"]!="") {
		echo $item["compute"]["vram"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>OpenGL Version</td><td>';
	if (strtoupper($item["compute"]["gpu"])=="NONE") {
		echo "<i>n/a</i>";
	} else if (strtoupper($item["compute"]["gpu"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["compute"]["ogl"]!="") {
		echo $item["compute"]["ogl"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>HDD</td><td>';
	if (strtoupper($item["compute"]["hdd"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["compute"]["hdd"]!="") {
		echo $item["compute"]["hdd"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>HDD Speed</td><td>';
	if (strtoupper($item["compute"]["hdd"])=="NONE") {
		echo "<i>n/a</i>";
	} else if ($item["compute"]["hdd_speed"]!="") {
		echo $item["compute"]["hdd_speed"];
	} else {
		echo $undefined;
	}
	echo '</td></tr></table><table><tr><th colspan="2" class="textsizebigger">Battery</th></tr><tr class="headone"><td>Tech</td><td>';
	if (strtoupper($item["battery"]["tech"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["battery"]["tech"]!="") {
		echo $item["battery"]["tech"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>Size</td><td>';
	if (strtoupper($item["battery"]["tech"])=="NONE") {
		echo "<i>n/a</i>";
	} else if ($item["battery"]["size"]!="") {
		echo $item["battery"]["size"];
	} else {
		echo $undefined;
	}
	echo '</td></tr><tr class="headone"><td>№ of Cells</td><td>';
	if (strtoupper($item["battery"]["tech"])=="NONE") {
		echo "<i>n/a</i>";
	} else if ($item["battery"]["cells"]!="") {
		echo $item["battery"]["cells"];
	} else {
		echo $undefined;
	}
	echo '</td></tr></table><table><tr><th class="textsizebigger">Notes</th></tr><tr class="headone"><td>';
	if (strtoupper($item["notes"])=="NONE") {
		echo "<i>none</i>";
	} else if ($item["notes"]!="") {
		echo $item["notes"];
	} else {
		echo "<i>none</i>";
	}
	echo '</td></tr></table><table><tr><th class="textsizebigger">Operations</th></tr><tr class="headone"><td>';
	if (whoitemout($_GET["item"]) != false) {
		echo "<a href=\"?item=" . $_GET["item"] . "&checkin\">Check In</a><span class=\"fr\">(out by " . name(whoitemout($_GET["item"])) . ")</span>";
	} else {
		echo '<form method="get" style="margin:0px"><input id="checkouti" type="hidden" value="' . $_GET["item"] . '" type="text" name="item" /><input id="checkoutt" style="width:100px;margin-right:4px;" type="text" name="checkout" placeholder=\'username\' /><button id="checkoutg" type="submit">Check Out</button></form>';
	}
	echo '</td></tr><tr class="headone"><td><a href="?item=' . $_GET["item"] . '&edit">Edit</a></td></tr></table>'; //<form method="post" style="margin:0px"><input type="hidden" value="true" name="edit" /><input type="hidden" value="' . $_GET["item"] . '" name="item" /><button type="submit">Edit</button></form>
}

function query($q) {
	echo '<table class="headtwo"><tr><th colspan="5" class="textsizebigger">Query Results (' . $q . ')</th></tr><tr><th>ID</th><th>Make</th><th>Model</th><th>Category</th><th>Out by</th></tr>';
	$q = strtoupper($q);
	global $items;
	for ($j = 0; $j < count($items); $j++) {
		$itemraw = file_get_contents("database/i" . $items[$j] . ".json");
		$itemraw = strtoupper($itemraw);
		if(strpos($itemraw,$q)!==false||strtoupper($q)=="ALL") {
//	    echo "\"bar\" exists in the haystack variable";
			$item = json_decode(file_get_contents("database/i" . $items[$j] . ".json"), true);
			echo '<tr class="headtwo">';
			echo '<td><a href="?item=' . $items[$j] . '">' . $items[$j] . '</td>';
			echo '<td>';
			if ($item["general"]["make"]=='') {
				echo "<i>unknown</i>";
			} else {
				echo ucfirst($item["general"]["make"]);
			}
			echo '</td><td>';
			if ($item["general"]["model"]=='') {
				echo "<i>unknown</i>";
			} else {
				echo ucfirst($item["general"]["model"]);
			}
			echo '</td><td>' . ucfirst($item["general"]["cat"]) . '</td>';
			echo '<td>';
			if (whoitemout($items[$j]) != false) {
//				$user = json_decode(file_get_contents("database/u" . whoitemout($items[$j]) . ".json"), true);
//				echo whoitemout($items[$j]);
//				echo $user["name"];
				echo '<!--<a href="?user=' . whoitemout($items[$j]) . '">-->' . whoitemout($items[$j]) . '<!--</a>-->';
			} else {
				echo "<i>nobody</i>";
			}
			echo '</td>';
			echo '</tr>';
		}/* else {
			if (strtoupper($q) == "ALL") {
				$item = json_decode(file_get_contents("database/i" . $items[$j] . ".json"), true);
				echo '<tr class="headtwo">';
				echo '<td><a href="?item=' . $items[$j] . '">' . $items[$j] . '</td>';
				echo '<td>' . ucfirst($item["general"]["make"]) . '</td>';
				echo '<td>' . ucfirst($item["general"]["model"]) . '</td>';
				echo '<td>' . ucfirst($item["general"]["cat"]) . '</td>';
				echo '<td>';
				if (whoitemout($items[$j]) != false) {
					echo whoitemout($items[$j]);
				} else {
					echo "<i>nobody</i>";
				}
				echo '</td>';
				echo '</tr>';
			}
		}*/
	}
	echo '</table>';
}

function name($uid) {
	$user = json_decode(file_get_contents("database/u" . $uid . ".json"), true);
	return $user["name"];
}

function errors(){
	if (isset($_SESSION["error"])) {
		echo '<div style="padding:10px;margin:10px;border:2px solid #F00;border-radius:4px;background-color:#FDD;color:#000"><b>ERROR: ' . $_SESSION["error"] . "</b></div>";
		unset($_SESSION["error"]);
	}
	if (isset($_GET["item"])) {
		if (!file_exists('database/i'.$_GET["item"].'.json')) {
			echo '<div style="padding:10px;margin:10px;border:2px solid #F00;border-radius:4px;background-color:#FDD;color:#000"><b>ERROR: Item ' . $_GET["item"] . ' doesn\'t exsist</b></div>';
			eof();
		}
		$item = json_decode(file_get_contents("database/i" . $_GET["item"] . ".json"), true);
		if (!isset($item["general"]["make"]) || !isset($item["general"]["line"]) || !isset($item["general"]["model"]) || !isset($item["general"]["serial"]) || !isset($item["general"]["cat"]) || !isset($item["general"]["subcat"]) || !isset($item["general"]["dom"]) || !isset($item["display"]["tech"]) || !isset($item["display"]["screen_size"]) || !isset($item["display"]["pixel_size"]) || !isset($item["display"]["aspect"]) || !isset($item["display"]["touch"]) || !isset($item["keyboard"]["tech"]) || !isset($item["keyboard"]["layout"]) || !isset($item["keyboard"]["backlight"]) || !isset($item["mouse"]["tech"]) || !isset($item["mouse"]["buttons"]) || !isset($item["mouse"]["scroll"]) || !isset($item["io"]["display"]) || !isset($item["io"]["networking"]) || !isset($item["io"]["usb"]) || !isset($item["io"]["optical_drive"]) || !isset($item["io"]["sound"]) || !isset($item["io"]["power"]) || !isset($item["io"]["edge"]) || !isset($item["io"]["other"]) || !isset($item["compute"]["cpu"]) || !isset($item["compute"]["cpu_speed"]) || !isset($item["compute"]["cores"]) || !isset($item["compute"]["ram"]) || !isset($item["compute"]["gpu"]) || !isset($item["compute"]["gpu_speed"]) || !isset($item["compute"]["vram"]) || !isset($item["compute"]["ogl"]) || !isset($item["compute"]["hdd"]) || !isset($item["compute"]["hdd_speed"]) || !isset($item["battery"]["tech"]) || !isset($item["battery"]["size"]) || !isset($item["battery"]["cells"]) || !isset($item["notes"])) {
			echo '<div style="padding:10px;margin:10px;border:2px solid #FF0;border-radius:4px;background-color:#FFD;color:#000"><b>WARNING: Item ' . $_GET["item"] . ' is not completely defined. Please edit and save to define it completely. This works even if not everything is filled in.</b></div>';
		}
	}
	if (isset($_GET["user"]) && !file_exists('database/u'.$_GET["user"].'.json')) {
		echo '<div style="padding:10px;margin:10px;border:2px solid #F00;border-radius:4px;background-color:#FDD;color:#000"><b>ERROR: User "' . $_GET["user"] . '" doesn\'t exsist</b></div>';
		eof();
	}
}

function success(){
	if (isset($_SESSION["success"])) {
		echo '<div style="padding:10px;margin:10px;border:2px solid #0F0;border-radius:4px;background-color:#DFD;color:#000"><b>SUCCESS: ' . $_SESSION["success"] . "</b></div>";
		unset($_SESSION["success"]);
	}
}

function debug(){
	echo '<div style="padding:10px;margin:10px;border:2px solid #00F;border-radius:4px;background-color:#DDF;color:#000;font-family:monospace;text-align:left;"><b><u>DEBUG: </b></u><br>';
	echo '<b>$_GET == </b>' . json_encode($_GET) . ";<br>";
	echo '<b>$_POST == </b>' . json_encode($_POST) . ";<br>";
	echo '<b>$item == </b>';
	if (isset($_GET["item"])) {
		echo htmlentities(file_get_contents("database/i" . $_GET["item"] . ".json"));
	} else {
		echo "<i>undefined</i>";
	}
	echo ";<br>";
	echo "</div>";
}

function eof(){
	global $version;
	global $date;
	echo '<hr/><span style="float:right"><i>' . $version . ' ' . $date . '</i></span></div></body></html>';
	exit();
}

/***********************************************************************************************************************
************************************************************************************************************************
************************************************************************************************************************
**********                                                                                                   ***********
**********                                          HTML PAGE STARTS                                         ***********
**********                                                                                                   ***********
************************************************************************************************************************
************************************************************************************************************************
***********************************************************************************************************************/


echo '<html>
	<head>
		<title>Yuft</title>
		<link rel="stylesheet" href="'.$version.'.css" />
		<link rel="shortcut icon" href="favicon.ico" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
	</head>
	<body>
		<div id="header">';

//echo '<a href="/">' . $_SERVER["HTTP_HOST"] . '</a> / ';
if (isset($_GET["user"]) || isset($_GET["item"]) || isset($_GET["query"]) || isset($_GET["make"]) || isset($_GET["cat"]) || isset($_GET["create"])) {
  echo '<a href=".">Yuft</a>';
  $home = false;
} else {
  echo 'Yuft';
  $home = true;
}
if (isset($_GET["edit"]) && strtoupper($_GET["edit"]) != "FALSE") {
	if (isset($_GET["user"])) {
		echo '&nbsp;&nbsp;&nbsp;&nbsp;<a href="?user=' . $_GET["user"] . '">User ' . $_GET["user"] . "</a>";
	}
	if (isset($_GET["item"])) {
		echo '&nbsp;&nbsp;&nbsp;&nbsp;<a href="?item=' . $_GET["item"] . '">Item ' . $_GET["item"] . "</a>";
	}
	echo "&nbsp;&nbsp;&nbsp;&nbsp;Edit";
} else {
	if (isset($_GET["user"])) {
		echo '&nbsp;&nbsp;&nbsp;&nbsp;User ' . $_GET["user"];
	}
	if (isset($_GET["item"])) {
		echo '&nbsp;&nbsp;&nbsp;&nbsp;Item ' . $_GET["item"];
	}
	if (isset($_GET["query"])) {
		echo '&nbsp;&nbsp;&nbsp;&nbsp;Query ' . $_GET["query"];
	}
	if (isset($_GET["make"])) {
		echo '&nbsp;&nbsp;&nbsp;&nbsp;Make ' . $_GET["make"];
	}
	if (isset($_GET["cat"])) {
		echo '&nbsp;&nbsp;&nbsp;&nbsp;Category ' . $_GET["cat"];
	}
	if (isset($_GET["create"])) {
		echo '&nbsp;&nbsp;&nbsp;&nbsp;Create ' . ucfirst($_GET["create"]);
	}
}

echo '<div id="query"><form method="get"><input id="queryt" type="text" name="query" ';
if (isset($_GET["query"])) {
	echo 'value="' . $_GET["query"] . '" ';
}
echo 'placeholder=\'Query (leave blank for all)\' /><button id="queryg" type="submit">Run</button></form></div></div>';

echo '<div id="body">';

errors();
success();
//debug();

if (isset($_GET["edit"]) && isset($_GET["item"]) && strtoupper($_GET["edit"]) != "FALSE") {
	edit($_GET["item"]);
} else if (isset($query)) {
  query($query);
} else if (isset($_GET["create"])) {
	create();
} else if (isset($_GET["item"])) {
	itemdetails();
} else if ($home) {
  itemsout(true);
	users();
	cats();
	makes();
	create();
}

eof();

?>
