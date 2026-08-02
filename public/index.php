<?php

require_once dirname(__DIR__, 4) . '/globals.php';

use OpenEMR\Modules\NeoLimsBridge\Repository\MessageRepository;

if (!acl_check('admin', 'super')) {
    http_response_code(403);
    exit(xlt('Access denied'));
}

$messages = (new MessageRepository())->search(25);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo xlt('NeoLIMS Integration'); ?></title>
    <link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative']; ?>/bootstrap/dist/css/bootstrap.min.css">
</head>
<body class="container-fluid py-3">
<h1><?php echo xlt('NeoLIMS Hybrid Integration'); ?></h1>
<p class="text-muted">
    <?php echo xlt('FHIR, Standard API, and HL7 v2 messages normalize into one idempotent queue.'); ?>
</p>
<table class="table table-sm table-striped">
<thead>
<tr>
    <th><?php echo xlt('Updated'); ?></th>
    <th><?php echo xlt('Transport'); ?></th>
    <th><?php echo xlt('Type'); ?></th>
    <th><?php echo xlt('Identifier'); ?></th>
    <th><?php echo xlt('Status'); ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($messages as $row): ?>
<tr>
    <td><?php echo text($row['updated_at']); ?></td>
    <td><?php echo text($row['transport']); ?></td>
    <td><?php echo text($row['message_type']); ?></td>
    <td><?php echo text($row['identifier_value']); ?></td>
    <td><?php echo text($row['status']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body>
</html>
