<?php
/**
**Plugin Name: SnapView
**Description: Demo WP plugin. Uses Wordpress API to capture a snapview (snapshot/overview) of the site. Creates an Admin menu item titled Pulse Settings OOP
* Version:           0.1
* Author:            JOd
* Author URI:        
* License:           GPL v2 or later
* License URI:       https://www.gnu.org/licenses/gpl-2.0.html
**/

session_start();

function admin_snapview_menu()
{
    add_menu_page('pulse Settings Page','Site SnapView','manage_options','site-xnapview','pulse_nor_form','',200);
}

add_action('admin_menu','admin_snapview_menu');

class pulse {

    private $url;

    function __construct($pulse_type) {
        $base = get_option('home');
        $this->url = $base . '/wp-json/wp/v2/' . $pulse_type;
    }
    function do_url_get($data_type) {
        echo "<h4>API fetch of {$_SESSION['ptype']} {$data_type} from: {$this->url}</h4>";
        $data = wp_remote_retrieve_body(wp_remote_get($this->url));

        return $data;
    }
}

class select_columns {
    function get_columns($pulse_type, $columns1) {
        $pulse_type = $_SESSION['ptype'];

        $output =  '
        <div class="wrap"><table>
        <form action="" method="POST">
            <label for="ptype"><h4>Select ' . $pulse_type . ' columns to fetch</h4></label>';
            
            foreach ($columns1 as $item) {
                $output .= '<tr><td>';
                $output .= $item . '</td><td><input type="checkbox" name="select_cols[]" id="select_cols" value="' . $item . '"><br />';
                $output .= '</td></tr>';
            }
            $output .= '<tr><td colspan="2"><input type="submit" name="pulse_fetch_btn" value="Submit" /></td></tr>
        </form>
        </table></div>';

        echo $output;
    }

}

class mslist {
    
    function tabulate($targets) {
        $output = '<div class="wrap"><table>';
        $number = 0;
    
        foreach ($targets as $target) {
            $items = get_object_vars($target);
            $number++;
            $output .= '<tr><th colspan="2">Item:  ' . $number . "</th></tr>";

            foreach ($items as $key => $value) {            
                if (is_object($value)) {
                    $output .= "<tr><th>".$key."</th><td>".$value->rendered."</td></tr>";
    
                } else {
                    $output .= "<tr><th>".$key."</th><td>".$value."</td></tr>";
                }
            }
        }

        $output .= "</table></div>\n";
        return $output;
    }

}

class Latest {

    function get_latest($pulse_type) {
        $wpd = $GLOBALS['wpdb'];

        $last = $wpd->get_results( "select id,post_type,post_title,post_author,post_modified from $wpd->posts where post_status = 'publish' order by post_modified desc limit 1" );
        if (!isset($last[0]->post_title)) {$title = $last[0]->post_title;} else {$title = 'NO-TITLE';}
        
        return "<h4>Lastest site update was a {$last[0]->post_type} update with ID {$last[0]->id}, at [{$last[0]->post_modified}], titled \"{$title}\", made by: {$last[0]->post_author}</h4>";
    }
}

function pulse_nor_form() {
 
    echo '
    <div class="wrap">
    <h3>SnapView Dashboard</h3>
    <form action="" method="POST">
        <label for="ptype">Select an entry type to begin: </label>
        <select name="ptype" id="ptype">
        <option value="posts">Posts</option>
        <option value="pages">Pages</option>
        <option value="media">Media</option>
        </select>
        <input type="submit" name="pulse_set_btn" value="Submit" />
    </form>
    </div>';
    

    $output1 = "";
    if (isset($_POST['ptype'])) { $pulse_type = $_POST['ptype'];}

    if ( array_key_exists('pulse_set_btn', $_POST)) {
        $pulse_type = $_POST['ptype'];
        $pulse_obj1 = new pulse($pulse_type);
        $columns = $pulse_obj1->do_url_get("columns list");

        $columns1 = json_decode($columns);
        $cols = get_object_vars($columns1[0]);

        $_SESSION['ptype'] = $pulse_type;

        $column_list = new select_columns();
        $output1 .= $column_list->get_columns($pulse_type, array_keys($cols));

    }

    if ( array_key_exists('pulse_fetch_btn', $_POST)) {
        $pulse_type = $_SESSION['ptype'];
        
        $cols = implode(',', $_POST['select_cols']);
        $fetch_url = $pulse_type . '?_fields=' . $cols;

        $pulse_obj = new pulse($fetch_url);
        $out = $pulse_obj->do_url_get("data");
        
        $output1 = "<h4>Results:</h4>";

        $out1 = json_decode($out);
        $table = new mslist();
        $output1 .= $table->tabulate($out1);

        $latest = new Latest();
        $last = $latest->get_latest($pulse_type);
        $output1 .= $last;

    }
    echo $output1;
}

?>