<?php
/**
 * Main plugin class for the reannotator.
 */
class IiifItemsReannotatePlugin extends Omeka_Plugin_AbstractPlugin {
    protected $_hooks = array(
        'install',
        'uninstall',
        'upgrade',
        'define_routes',
    );

    protected $_filters = array(
    );
    
    /**
     * Hook: Installation
     * Add database and option entries associated with this plugin.
     */
    public function hookInstall() {
    	$db = $this->_db;
        $db->query("CREATE TABLE IF NOT EXISTS {$db->prefix}iiif_items_reannotate_jobs (
            id INT(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            source_collection_id INT(10) UNSIGNED NOT NULL,
            target_collection_id INT(10) UNSIGNED NOT NULL,
            created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (source_collection_id) REFERENCES {$db->prefix}collections(id) ON DELETE CASCADE,
            FOREIGN KEY (target_collection_id) REFERENCES {$db->prefix}collections(id) ON DELETE CASCADE
        );");
        $db->query("CREATE TABLE IF NOT EXISTS {$db->prefix}iiif_items_reannotate_mappings (
            id INT(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            job_id INT(10) UNSIGNED NOT NULL,
            source_item_id INT(10) UNSIGNED NOT NULL,
            target_item_id INT(10) UNSIGNED NOT NULL,
            FOREIGN KEY (job_id) REFERENCES {$db->prefix}iiif_items_reannotate_jobs(id) ON DELETE CASCADE,
            FOREIGN KEY (source_item_id) REFERENCES {$db->prefix}items(id) ON DELETE CASCADE,
            FOREIGN KEY (target_item_id) REFERENCES {$db->prefix}items(id) ON DELETE CASCADE
        );");
    }

    /**
     * Hook: Uninstallation
     * Remove database and option entries associated with this plugin.
     */
    public function hookUninstall() {
        $db = $this->_db;
        $db->query("DROP TABLE IF EXISTS {$db->prefix}iiif_items_reannotate_mappings");
        $db->query("DROP TABLE IF EXISTS {$db->prefix}iiif_items_reannotate_jobs");
    }

    /**
     * Hook: Update
     * Sequentially pull migrations to update database and option entries.
     * 
     * @param array $args
     */
    public function hookUpgrade($args) {
    	$oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];
        $doMigrate = false;

        $versions = array();
        foreach (glob(dirname(__FILE__) . '/libraries/IiifItemsReannotate/Migration/*.php') as $migrationFile) {
            $className = 'IiifItems_Migration_' . basename($migrationFile, '.php');
            include $migrationFile;
            $versions[$className::$version] = new $className();
        }
        uksort($versions, 'version_compare');

        foreach ($versions as $version => $migration) {
            if (version_compare($version, $oldVersion, '>')) {
                $doMigrate = true;
            }
            if ($doMigrate) {
                $migration->up();
                if (version_compare($version, $newVersion, '>')) {
                    break;
                }
            }
        }
    }

    /**
     * Hook: Define routes
     * Add plugin-specific routes.
     * 
     * @param array $args
     */
    public function hookDefineRoutes($args) {
        if (is_admin_theme()) {
            $args['router']->addConfig(new Zend_Config_Ini(dirname(__FILE__) . '/routes.ini', 'admin_routes'));
        }
    }
}
