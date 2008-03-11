<!doctype html public "-//w3c//dtd xhtml 1.0 transitional//en" "http://www.w3.org/tr/xhtml1/dtd/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
<title>pedantic system admin - migrate</title>
<?=default_scripts();?>
<style type="text/css">
body {
    font-family: tahoma, verdana, arial;
}
.migration {
    margin-bottom: 10px;
}
.migration h4 {
    margin: 0; padding: 0;
    font-weight: normal;
}
.migration a {
    color: #000;
    font-size: 0.8em; 
    text-decoration: none;
}
.success {
    display: block;
    margin: 0; padding: 0;
}
</style>
</head>
<body>
    <p><?=proper_nounize(APP_NAME);?> schema is currently at version <?=$schema_version;?></p><?

    foreach ($sys->migrations as $migration)
    {
        if ($migration['version'] > $schema_version)
        {
            ?><div class="migration"><span class="description">version <?=$migration['version']?> - <i><?=$migration['description'];?></i></span>
            <span class="success">successfully migrated to version <?=$migration['version'];?></span>&nbsp;
            </div><?
        }
    }
    <p><?=proper_nounize(APP_NAME);?> schema has been migrated to version <?=$migration['version'];?></p><?

    echo $schema_interrregator_results;
    #successfully completed
    ?><script type="text/javascript">document.title='<?=proper_nounize(APP_NAME);?> | migration complete';</script>
</body>
</html>
