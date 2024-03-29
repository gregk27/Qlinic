<?php
include $_SERVER["DOCUMENT_ROOT"] . "/backend/config.php";
//include $_SERVER["DOCUMENT_ROOT"] . "/backend/utils.php";

define("GET_DOCS", "SELECT ID,server FROM qlinic.available");
define("GET_DOC_INFO", "SELECT * FROM qlinic.available WHERE ID = ?");
define("GET_ALL_BOOKED", "SELECT * from qlinic.booked ORDER BY date");
define("GET_BY_DATE", "SELECT (UNIX_TIMESTAMP(date)+time) FROM qlinic.booked WHERE date = ? ORDER BY time");
define("GET_BY_DATE_AND_SERVER", "SELECT (UNIX_TIMESTAMP(date)+time) FROM qlinic.booked WHERE date = ? AND server = ? ORDER BY time");
define("GET_ALL_IN_RANGE", "SELECT (UNIX_TIMESTAMP(date)+time),server,length,code FROM qlinic.booked WHERE date>? AND date<?");
define("BOOK_APPOINTMENT", "INSERT INTO qlinic.booked (firstname, lastname, server, date, time, length, reason, email, phone, code, transac) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
define("GET_APPOINTMENT_DETAILS", "SELECT * from qlinic.booked WHERE code = ?");
define("REMOVE_APPOINTMENT", "DELETE FROM qlinic.booked WHERE code = ?");
define("CHECK_APPOINTMENT_CODE", "SELECT code FROM qlinic.booked WHERE code=?");
define("GET_AGENDA_DETAILS", "SELECT * FROM qlinic.booked WHERE date = ? ORDER BY time");

function getDateString($timestamp){
    if(!is_numeric($timestamp)){
        return $timestamp;
    }
    return date("Y-m-d", (int) $timestamp);
}

function getDateTimestamp($date){
    //Make it unix representation
    if(!is_numeric($date)){
        $date = strtotime($date);
    }
    //Trim time
    $date = date("Y-m-d", $date);
    //Return unix
    return strtotime($date);
}

function removeAppointment($code){
    $stmt = createStatement(REMOVE_APPOINTMENT);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $stmt->free_result();
}

function getAppointmentDetails($code){
    $stmt = createStatement(GET_APPOINTMENT_DETAILS);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->free_result();
    return $result;
}

function getAgendaDetails($date){
    $date = getDateString($date);
    $stmt=createStatement(GET_AGENDA_DETAILS);
    $stmt->bind_param("s", $date);

    $stmt->execute();
    $result = $stmt->get_result();
    $out = [];
    while($row = $result->fetch_assoc()){
        array_push($out, $row);
    }
    $stmt->free_result();
    return $out;
}

/**
 * Get a list of possible times for a specific doctor
 * @param $server int ID of the target server
 * @param $date mixed UNIX timestamp for 0:00 (midnight) on selected date, or string in format YYYY-MM-DD
 * @return array list of possible times, in unix timestamps
*/
function getDocPossibleTimes($date, $server){
    $stmt = createStatement(GET_DOC_INFO);
    $stmt->bind_param("i", $server);
    $stmt->execute();
    $result=$stmt->get_result()->fetch_assoc();
    $stmt->free_result();

    $date = getDateTimestamp($date);
    $start = $date + $result["start"];
    $end = $date + $result["end"];
    $len = $result["len"];

    $out = [];

    for($time=$start; $time+$len<=$end; $time+=$len){
        array_push($out, $time);
    }
    return $out;
}

/**
 * Get the possible times for all doctors
 * @param $date mixed Target day
 * @return array This list of available times sorted by doctor
*/
function getAllPossibleTimes($date){
    $stmt = createStatement(GET_DOCS);

    $stmt->execute();
    $ID="";
    $name="";
    $stmt->bind_result($ID, $name);
    $stmt->store_result();
    $out = [];
    while($stmt->fetch()){
        $out[$ID] = getDocPossibleTimes($date,$ID);
    }

    $stmt->free_result();
    return $out;
}

/**
 * Get the server names and IDs
 * @return array Assoc array of servers, indexed by ID
*/
function getServers(){
    $stmt = createStatement(GET_DOCS);
    $stmt->execute();
    $out = [];
    $stmt->bind_result($ID, $server);
    while($stmt->fetch()){
        $out[$ID] = $server;
    }
    $stmt->free_result();
    return $out;
}

/**
 * Get basic information for all appointments booked within a timeframe
 * @param $start mixed The start of the timeframe
 * @param $end mixed The end of the timeframe
 * @return array Associative array containing: server, time, length, and code
 */
function getInRange($start, $end){
    $stat = getDateString($start);
    $end = getDateString($end);
    $stmt = createStatement(GET_ALL_IN_RANGE);
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    $stmt->bind_result($time, $server,$len,$code);
    $stmt->store_result();
    $out = [];
    while($stmt->fetch()){
        array_push($out, array("server"=>$server, "time"=>$time, "length"=>$len, "code"=>$code));
    }
    $stmt->free_result();
    return $out;
}

/**
 * Get all booked appointments
*/
function getAllAppointments(){
    return getInRange(0,"2038-01-01");
}

/**
 * Get appointments booked on a specific date
 * @param $date mixed The unix timestamp for midnight on the target date or date string in format YYYY-MM-DD
 * @param $server int The ID of the server. If left null will get all servers.
 * @return array The list of booked appointment timestamps
 */
function getBookedOnDate($date, $server=null){
    $date = getDateString($date);
    if($server==null){
        $stmt = createStatement(GET_BY_DATE);
        $stmt->bind_param("s", $date);
    } else {
        $stmt = createStatement(GET_BY_DATE_AND_SERVER);
        $stmt->bind_param("si", $date, $server);
    }
    $stmt->execute();
    $stmt->bind_result($time);

    $out = [];
    while ($stmt->fetch()){
        array_push($out, $time);
    }
    $stmt->free_result();
    return $out;
}

/**
 * Get available appointment slots on a given date
 * @param $date mixed The unix timestamp for midnight on the target date, or string in format YYYY-MM-DD
 * @return array Associative array with array of times under server IDs
*/
function getAvailable($date){
    $possible = getAllPossibleTimes($date);
    $out = [];
    foreach($possible as $server=>$times){
//        echo $server.":<br/>";
//        echo implode(",", $times)."<br/>";
        $booked = getBookedOnDate($date, $server);
//        echo implode(",", $booked)."<br/>";
        $available = array_diff($times, $booked);
        $out[$server] = $available;
    }
    return $out;
}


function checkAppointmentCode($code){
	$stmt=createStatement(CHECK_APPOINTMENT_CODE);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    if($stmt->num_rows ==0){
        $stmt->free_result();
        return true;
    }
    $stmt->free_result();
    return false;
}

function book($firstname, $lastname, $server, $date, $time, $length, $reason, $email, $phone, $transac, &$code){
    $stmt = createStatement(BOOK_APPOINTMENT);
    //Create unique code
	$unique = false;
	while(!$unique){
		$code = substr(MD5($firstname.$lastname.$time), 0,5);
		$unique=checkAppointmentCode($code);
	}
    $stmt->bind_param("ssisiisssss", $firstname, $lastname, $server, $date, $time, $length, $reason, $email, $phone, $code, $transac);
    $stmt->execute();
    if($stmt->error == ""){
        return true;
    } else {
        $code = $stmt->error;
        return false;
    }
}





