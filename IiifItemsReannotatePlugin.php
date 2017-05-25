<?php
/**
 * Main plugin class for the reannotator.
 */
class IiifItemsReannotatePlugin extends Omeka_Plugin_AbstractPlugin {
    
    /**
     * List of hooks used by this plugin.
     * @var array
     */
    protected $_hooks = array(
        'install',
        'uninstall',
        'upgrade',
        'define_routes',
    );

    /**
     * List of filters used by this plugin.
     * @var array
     */
    protected $_filters = array(
        'admin_navigation_main',
    );
    
    /**
     * Hook: Installation
     * Add database and option entries associated with this plugin.
     */
    public function hookInstall() {
    	$db = $this->_db;
        // Tasks table
        $db->query("CREATE TABLE IF NOT EXISTS {$db->prefix}iiif_items_reannotate_tasks (
            id INT(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            source_collection_id INT(10) UNSIGNED NOT NULL,
            target_collection_id INT(10) UNSIGNED NOT NULL,
            created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (source_collection_id) REFERENCES {$db->prefix}collections(id) ON DELETE CASCADE,
            FOREIGN KEY (target_collection_id) REFERENCES {$db->prefix}collections(id) ON DELETE CASCADE
        );");
        // Mappings table
        $db->query("CREATE TABLE IF NOT EXISTS {$db->prefix}iiif_items_reannotate_mappings (
            id INT(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            task_id INT(10) UNSIGNED NOT NULL,
            source_item_id INT(10) UNSIGNED NOT NULL,
            target_item_id INT(10) UNSIGNED DEFAULT NULL,
            source_x INT(10) NOT NULL DEFAULT 0,
            source_y INT(10) NOT NULL DEFAULT 0,
            source_w INT(10) NOT NULL DEFAULT 0,
            source_h INT(10) NOT NULL DEFAULT 0,
            target_x INT(10) NOT NULL DEFAULT 0,
            target_y INT(10) NOT NULL DEFAULT 0,
            target_w INT(10) NOT NULL DEFAULT 0,
            target_h INT(10) NOT NULL DEFAULT 0,
            FOREIGN KEY (task_id) REFERENCES {$db->prefix}iiif_items_reannotate_tasks(id) ON DELETE CASCADE,
            FOREIGN KEY (source_item_id) REFERENCES {$db->prefix}items(id) ON DELETE CASCADE,
            FOREIGN KEY (target_item_id) REFERENCES {$db->prefix}items(id) ON DELETE CASCADE
        );");
        // Status table
        $db->query("CREATE TABLE IF NOT EXISTS {$db->prefix}iiif_items_reannotate_statuses (
            id INT(10) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            source VARCHAR(255) NOT NULL,
            dones INT(10) NOT NULL,
            skips INT(10) NOT NULL,
            fails INT(10) NOT NULL,
            status VARCHAR(32) NOT NULL,
            progress INT(10) NOT NULL DEFAULT 0,
            total INT(10) NOT NULL DEFAULT 100,
            added TIMESTAMP NOT NULL DEFAULT '2017-05-01 18:00:00',
            modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );");
    }

    /**
     * Hook: Uninstallation
     * Remove database and option entries associated with this plugin.
     */
    public function hookUninstall() {
        $db = $this->_db;
        $db->query("DROP TABLE IF EXISTS {$db->prefix}iiif_items_reannotate_mappings");
        $db->query("DROP TABLE IF EXISTS {$db->prefix}iiif_items_reannotate_tasks");
        $db->query("DROP TABLE IF EXISTS {$db->prefix}iiif_items_reannotate_statuses");
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
        $args['router']->addConfig(new Zend_Config_Ini(dirname(__FILE__) . '/routes.ini', 'admin_routes'));
    }
    
    /**
     * Filter: Entries in the main admin navigation.
     * Add navigation link to the annotation remapper task and status screen.
     * 
     * @param array $nav
     * @return array
     */
    public function filterAdminNavigationMain($nav) {
        if (current_user()->role != 'researcher') {
            $nav[] = array(
                'label' => __('Annotation Remapper'),
                'uri' => url(array(), 'IiifItemsReannotate_Tasks'),
            );
        }
        return $nav;
    }
}
