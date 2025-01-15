<?php
//ini_set('display_errors', 1);
define('IN_WEBADMIN', true);

require_once("./config.php");
require_once("./initialize.php");

if (hmailGetAdminLevel() != 2)
	hmailHackingAttemp();

if(!empty($_POST['LiveLogging'])){
	$obSettings = $obBaseApp->Settings();
	$obLogging = $obSettings->Logging();
	$_SESSION['livelogging'] = $_POST['LiveLogging'];
	if($_POST['LiveLogging']=='enabled'){
		$obLogging->EnableLiveLogging(true);
		EchoTranslation("Stop");
	} else {
		$obLogging->EnableLiveLogging(false);
		EchoTranslation("Start");
	}
	exit();
}

function ParseFile($Filepath, $AllTypes, $Filter, $RawType, $Types){
    if(file_exists($Filepath)) {
        $Filesize = filesize($Filepath);
        $File = fopen($Filepath, 'r');

        if ($File) {
            require_once("./include/log_functions.php");
            while (($Line = fgets($File)) !== false) {
                if ($RawType){
                    if (!isset($events[0])) $events[0][0] = array('RAW');
                    $events[0][1][] = htmlentities(cleanNonUTF8($Line));
                    continue;
                }

                $Unfiltered = $Line;
                $Filtered = $AllTypes ? $Unfiltered :filter_result_type($Unfiltered, $Types);
                if (!is_null($Filter)) {
                    
                    $Filtered = filter_result($Filtered, $Filter, false);
                    $Filtered = preg_replace("/\w*?$Filter\w*/i", "{em}$0{/em}", $Filtered);
                }

                if (!is_null($Filtered)) parse($Filtered);
            }
            fclose($File);
            return events();
        } else {
            return Translate("Error opening log file");
        }
    } else {
        return Translate("Error opening log file");
    }
}

function ParseStream($MemoryStream, $AllTypes, $Filter, $RawType, $Types){
    if ($MemoryStream) {
        require_once("./include/log_functions.php");
        while (($Line = fgets($MemoryStream)) !== false) {
            if ($RawType){
                if (!isset($events[0])) $events[0][0] = array('RAW');
                $events[0][1][] = htmlentities(cleanNonUTF8($Line));
                continue;
            }

            $Unfiltered = $Line;
            $Filtered = $AllTypes ? $Unfiltered :filter_result_type($Unfiltered, $Types);
            if (!is_null($Filter)) {
                
                $Filtered = filter_result($Filtered, $Filter, false);
                $Filtered = preg_replace("/\w*?$Filter\w*/i", "{em}$0{/em}", $Filtered);
            }

            if (!is_null($Filtered)) parse($Filtered);
        }
        return events();
    } else {
        return Translate("Error opening log file");
    }
}

$Types = !empty($_POST['LogTypes']) ? $_POST['LogTypes'] : array('SMTPD');
$AllTypes = in_array('ALL', $Types);
$RawType = !empty($_POST['LogType']) ? true : false;
$Filter = !empty($_POST['LogFilter']) ? $_POST['LogFilter'] : null;
$Filedate = !empty($_POST['LogFilename']) ? $_POST['LogFilename'] : date("Y-m-d");
$Filename = 'hmailserver_' . $Filedate . '.log';
$Path = $obBaseApp->Settings->Directories->LogDirectory;
$Filename = $Path . '\\' . $Filename;
// SepSvcLogs=1?
$inipath = $obBaseApp->Settings->Directories->ProgramDirectory . "Bin\hMailServer.ini";
$ini = [];
if(file_exists($inipath)){
    $ini = parse_ini_file($inipath, false, INI_SCANNER_RAW);
}

if (array_key_exists('SepSvcLogs', $ini) && $ini['SepSvcLogs'] == "1") {
    $SmtpFile = $Path . '\\hmailserver_SMTP_' . $Filedate . '.log';
    $ImapFile = $Path . '\\hmailserver_IMAP_' . $Filedate . '.log';
    $Pop3File = $Path . '\\hmailserver_POP3_' . $Filedate . '.log';
    $ErrorFile = $Path . '\\ERROR_hmailserver_' . $Filedate . '.log';
    
    $SmtpFileParts = $ImapFileParts = $Pop3FileParts = $ErrorFileParts = [];
    if (file_exists($SmtpFile)) {
        $SmtpFileParts = file($SmtpFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    if (file_exists($ImapFile)) {
        $ImapFileParts = file($ImapFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    if (file_exists($Pop3File)) {
        $Pop3FileParts = file($Pop3File, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    if (file_exists($ErrorFile)) {
        $ErrorFileParts = file($ErrorFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    
    // Merge all parts into a single array
    $AllLines = array_merge($SmtpFileParts, $ImapFileParts, $Pop3FileParts, $ErrorFileParts);
    
    // Sort lines by timestamp
    usort($AllLines, function ($lineA, $lineB) {
        // Extract timestamps using regex
        preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3}/', $lineA, $matchesA);
        preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{3}/', $lineB, $matchesB);
        
        $timestampA = $matchesA[0] ?? '';
        $timestampB = $matchesB[0] ?? '';
        
        return strcmp($timestampA, $timestampB);
    });
    
    $MaxMem = 40 * 1024 * 1024;
    $tempStream = fopen("php://temp/maxmemory:$MaxMem", 'r+');
    fwrite($tempStream, implode(PHP_EOL, $AllLines));
    rewind($tempStream);
    //file_put_contents($MergedFile, implode(PHP_EOL, $AllLines));
    $out = ParseStream($tempStream, $AllTypes, $Filter, $RawType, $Types);
    
    fclose($tempStream);
} elseif(file_exists($Filename)){
    $out = ParseFile($Filename, $AllTypes, $Filter, $RawType, $Types);
} else {
	$out = Translate("Log file not found");
}

header('Content-Type: application/json');
$out = json_encode($out);
echo $out;