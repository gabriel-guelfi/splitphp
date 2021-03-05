<!-- <script src="http://code.jquery.com/jquery-latest.min.js"></script> -->
<!-- <script src="<?php // echo $uri; ?>alert.js"></script> -->
<!-- <link rel="stylesheet" href="<?php // echo $uri; ?>alert.css"> -->
<?php
// Shows all alerts setted in dialogs to user.(See Alert::add method).
if (!empty($_SESSION['alerts'])) :
    $i = 0;
    foreach ($_SESSION['alerts'] as $alert) :
        ?>
        <!-- <div class="alert alert-<?php echo $alert->type; ?>" data-index="<?php echo $i; ?>">
            <img src="<?php // echo $uri; ?>img/alert-<?php // echo $alert->type; ?>.png">
            <span title="Close alert" class="close-alert">[X]</span>
            <p><?php // echo $alert->msg; ?></p>
        </div> -->
        <script>
            jQuery.notify({
                "message": "<?php echo $alert->msg; ?>"
            }, {
                "type": "<?php echo $alert->type; ?>"
            });
        </script>
<?php
        unset($_SESSION['alerts'][$i]);
        $i++;
    endforeach;
endif;
?>