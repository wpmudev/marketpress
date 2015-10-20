<div class="wrap theme-options">
    <div id="icon-tools" class="icon32"><br /></div>
    <h2><?php _e( 'Import external Gardens', 'mp' ); ?></h2><br />
    <form action="tools.php?page=import_external_gardens" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="process" />
        <div class="postbox metabox-holder">
            <h3 class="hndle"><?php _e( 'Import external Gardens', 'mp' ); ?></h3>
            <div class="inside">
                <div class="setting-panel">
                    <p>
                        <?php _e( 'Choose CSV Data file', 'mp' ); ?>
                        <input type="file" name="datafile" size="40" \>
                    </p>
                    <!--<p>
                        <?php _e( 'CSV field separator', 'mp' ); ?>
                        <input type="text" name="field_separator" size="40" \>
                    </p>
                    <p>
                        <?php _e( 'CSV text separator', 'mp' ); ?>
                        <input type="text" name="text_separator" size="40" \>
                    </p>-->
                    <p>
                        <?php _e( 'GMap API KEY', 'mp' ); ?>
                        <input type="text" name="gmap_api_key" size="40" \>
                    </p>
                </div>
            </div>
        </div>
        <p class="submit">
            <input type="submit" value="<?php _e( 'Import Gardens', 'mp' ); ?>" class="button-primary">
        </p>
        <ul id="response">
            <?php foreach( $messages as $message ) : ?>
                <li><?php print $message; ?></li>
            <?php endforeach; ?>
        </ul>
    </form>
</div>