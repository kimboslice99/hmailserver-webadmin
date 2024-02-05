<?php
if (!defined('IN_WEBADMIN'))
    exit();

if (hmailGetAdminLevel() != 2)
    hmailHackingAttemp();

if (empty($hmail_config['tlsreport_enable'])) exit('<div class="box large"><h2>TLS reports</h2><p class="warning">TLS reports are not enabled in config.php</p></div>');


function get_reports() {
    global $hmail_config;
    if (!extension_loaded('imap'))
        return Translate("IMAP extension not enabled in php.ini");

    if($hmail_config['tlsreport_encryption'])
        $hmail_config['tlsreport_encryption'] = '/' . $hmail_config['tlsreport_encryption'];
    $hostname = '{' . $hmail_config['tlsreport_hostip'] . ':' . $hmail_config['tlsreport_port'] . $hmail_config['tlsreport_encryption'] . '/novalidate-cert}INBOX';

    /* try to connect */
    if (!$inbox = @imap_open($hostname, $hmail_config['tlsreport_username'], $hmail_config['tlsreport_password']))
        return Translate("Cannot connect to server") . ': ' . imap_last_error();

    $folder = './tlsreports';
    $count = 0;
    //$emails = imap_search($inbox, 'UNSEEN');
    $emails = imap_search($inbox, 'ALL');

    /* if any emails found, iterate through each email */
    if ($emails) {
        /* for every email... */
        foreach ($emails as $email_number) {
            /* get mail structure */
            $structure = imap_fetchstructure($inbox, $email_number);

            $attachments = array();

            /* if any attachments found... */
            if (isset($structure->parts) && count($structure->parts)) {
                for ($i = 0; $i < count($structure->parts); $i++) {
                    $count += save_json_attachment($inbox, $email_number, $structure->parts[$i], $i, $folder);
                }
            } else {
                $count += save_json_attachment($inbox, $email_number, $structure, 0, $folder);
            }

            /* mark for delete */
            imap_delete($inbox, $email_number);
        }
    }

    /* close the connection and delete marked messages */
    imap_close($inbox, CL_EXPUNGE);

    return $count;
}

function save_json_attachment($inbox, $email_number, $part, $index, $folder) {
    $is_attachment = false;
    $filename = '';

    if ($part->ifdparameters) {
        foreach ($part->dparameters as $object) {
            if (strtolower($object->attribute) == 'filename') {
                $is_attachment = true;
                $filename = strtolower($object->value);
            }
        }
    }

    if ($part->ifparameters) {
        foreach ($part->parameters as $object) {
            if (strtolower($object->attribute) == 'name') {
                $is_attachment = true;
                $name = strtolower($object->value);
            }
        }
    }

    if ($is_attachment) {
        if (empty($filename))
            $filename = $name;

        $data = imap_fetchbody($inbox, $email_number, $index + 1);

        /* 3 = BASE64 encoding */
        if ($part->encoding == 3)
            $data = base64_decode($data);
        /* 4 = QUOTED-PRINTABLE encoding */
        elseif ($part->encoding == 4)
            $data = quoted_printable_decode($data);

        if (!is_dir($folder))
            mkdir($folder);

        // Assuming the attachment is already a JSON file
        $filename = str_replace('.gz', '', $filename); // Remove .gz extension if present
        $filename = str_replace('.json.gz', '.json', $filename);
        if ($data = gzdecode($data)) {
            file_put_contents($folder . '/' . $filename, $data);
        }
        else {
            file_put_contents($folder . '/' . $filename, $data);
        }

        return 1;
    }

    return 0;
}


/* Search directory for reports. */
$new_report_count = get_reports();
$files = glob('./tlsreports/*.json');
$reports_count = count($files);
if (!empty($files)) $reports = parse($files);
else $reports = array();

function parse($files){
    $out = array();
    
    if (!is_array($files))
        $files = array($files);
    
    foreach($files as $file){
        $json = file_get_contents($file);
        
        $data = json_decode($json, true);
        
        $out[] = array(
            'filename' => $file,
            'org' => $data['organization-name'],
            'date-range' => array(
                'date-begin' => $data['date-range']['start-datetime'],
                'date-end' => $data['date-range']['end-datetime'],
            ),
            'contact-info' => $data['contact-info'],
            'report-id' => $data['report-id'],
            'policies' => $data['policies']
        );
    }
    return $out;
}
//print_r( $reports );
?><script type="text/javascript">
$(document).ready(function(){
    if($('a.toggle').length){
        $('a.toggle').on('click', function() {
            var id = $(this).attr('id');
            var sign = $(this).text();
            if(sign == '+'){
                $('#' + id + '-d').show().find('div.hidden').slideDown(150);
                $(this).text('-');
            } else {
                $('#' + id + '-d').find('div.hidden').slideUp(150,function(){$('#' + id + '-d').hide()});
                $(this).text('+');
            }
            return false;
        })
    }
});
</script>
<style>
td.aligned {background-color:#9f9; padding-left:5px;}
td.unaligned {background-color:#f99; padding-left:5px;}
table.tls tbody tr:nth-child(3n) {background: #f9fafa}
table.tls tbody tr:nth-child(2n) {background: #eaeaea}
table.tls tbody tr:hover {background: #f2f2f2}
.tls table {margin:15px 0}
div.tls {overflow-x:auto}
}
</style>
    <div class="box large">
      <h2><?php EchoTranslation("TLS reports") ?> <span>(<?php echo $reports_count ?>)</span></h2>
<?php
    if(!empty($new_report_count))
    {
        if(is_int($new_report_count))
            echo '<div>' . str_replace('#',$new_report_count,Translate("# new reports added.")) . '</div>';
        else
            echo '<p class="warning">' . $new_report_count . '</p>';
    }
    $id = 0;
    foreach( $reports as $report ) {
        echo '<h3><a href="#">'.$report['org'].' &#8211; '.$report['date-range']['date-begin'].'</a></h3>';
?>
      <div class="hidden tls">
        <div class="buttons"><a class="button" href="#" onclick="return Confirm('<?php EchoTranslation("Confirm delete") ?> <b><?php EchoTranslation("TLS report") ?></b>','<?php EchoTranslation("Yes") ?>','<?php EchoTranslation("No") ?>','?page=background_tlsreports&tls=<?php echo $report['filename'] ?>&csrftoken=<?php echo $csrftoken ?>');"><?php EchoTranslation("Delete TLS report") ?></a></div>
        <h4 style="margin-top:18px;"><?php EchoTranslation("TLS Report Details") ?></h4>
        <table>
          <tr>
            <th><?php EchoTranslation("Provider") ?>:</th>
            <td><?= $report['org'] ?></td>
            <th><?php EchoTranslation("Report ID") ?>:</th>
            <td><?= $report['report-id'] ?></td>
          </tr>
          <tr>
            <th><?php EchoTranslation("Coverage") ?>:</th>
            <td><?= $report['date-range']['date-begin'] ?> - <?php echo $report['date-range']['date-end'] ?></td>
            <th><?php EchoTranslation("Extra contact") ?>:</th>
            <td><?= $report['contact-info'] ?></td>
          </tr>
        </table>
        <h4><?php EchoTranslation("Policy Details") ?></h4>
        <table>
        <?php foreach( $report['policies'] as $policy){ ?>
          <tr>
            <th><?php EchoTranslation('Policy Type') ?></th>
            <td><?= $policy['policy']['policy-type'] ?></td>
            <th><?php EchoTranslation('Policy') ?></th>
            <td><?= implode(', ', $policy['policy']['policy-string']) ?></td>
          </tr>
          <tr>
            <th><?php EchoTranslation('Policy Domain') ?></th>
            <td><?= $policy['policy']['policy-domain'] ?></td>
            <th><?php EchoTranslation('MX Hosts') ?></th>
            <td><?= implode(', ', $policy['policy']['mx-host']) ?></td>
          </tr>
        </table>
        <h4><?php EchoTranslation('Summary') ?></h4>
        <table class="tls">
          <tr>
            <th><?php EchoTranslation('Total Successful Session Count') ?></th>
            <td><?= $policy['summary']['total-successful-session-count'] ?></td>
            <th><?php EchoTranslation('Total Failure Session Count') ?></th>
            <td><?= $policy['summary']['total-failure-session-count'] ?></td>
          </tr>
        </table>
        <?php } ?>
          
<?php

    }
?>