<?php
namespace CodeRex\Telemetry;

class Queue {
    /**
     * The name of the custom table
     *
     * @var string
     */
    private string $table_name;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'coderex_telemetry_queue';
    }

    /**
     * Create the custom table
     *
     * @return void
     */
    public function create_table(): void {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            event varchar(255) NOT NULL,
            properties longtext NOT NULL,
            timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Add an event to the queue
     *
     * @param string $event
     * @param array $properties
     * @return void
     */
    public function add( string $event, array $properties ): void {
        global $wpdb;

        $wpdb->insert(
            $this->table_name,
            [
                'event'      => $event,
                'properties' => wp_json_encode( $properties ),
                'timestamp'  => current_time( 'mysql' ),
            ]
        );
    }

    /**
     * Get all events from the queue
     *
     * @return array
     */
    public function get_all(): array {
        global $wpdb;

        $results = $wpdb->get_results( "SELECT * FROM {$this->table_name} ORDER BY timestamp ASC" );

        return $results;
    }

    /**
     * Delete events from the queue
     *
     * @param array $ids
     * @return void
     */
    public function delete( array $ids ): void {
        global $wpdb;

        $ids = implode( ',', array_map( 'absint', $ids ) );

        $wpdb->query( "DELETE FROM {$this->table_name} WHERE id IN ($ids)" );
    }
}
