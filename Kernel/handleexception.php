<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css"/>
    <title><?php echo $title ?></title>
</head>

<div class="container">
    <br/>
    <div class="panel panel-default">
        <div class="panel-heading">Catappa Erorr Handler</div>
        <div class="panel-body">

        <ul>
            <li> Error code : <?php echo $e->getCode(); ?></li>
            <li> File : <?php echo $e->getFile(); ?></li>
            <li> Line : <?php echo $e->getLine(); ?></li>
            <li> Message :<b> <?php echo $e->getMessage(); ?></b></li>
        </ul>
       </div>
    </div>
     <hr/>
    <pre class="pre"> <?php echo $e->getTraceAsString(); ?></pre>
</div>
</html>