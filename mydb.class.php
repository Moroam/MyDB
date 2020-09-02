<?php
/**
 * Class MyDB
 *
 * @version 1.0.2
 */
class MyDB
{
  protected static $mysqli = null;

  final private function __construct() {}
  final private function __clone() {}

  /**
   * Return the instance of mysqli
   */
  public static function mysqli(bool $error_reporting = false) : mysqli {
    if(self::$mysqli === null) {
      try {
        self::$mysqli = new mysqli(HOST, USER, PASSWORD, DATABASE);
        defined('DB_CHAR') && self::$mysqli->set_charset(CHARSET);
      } catch (mysqli_sql_exception $e) {
        die('Database connection could not be established.');
      }
    }

    return self::$mysqli;
  }

  /**
   * Enables or disables internal reporting functions
   */
  public static function rep(bool $enable = true) : void {
    if( $enable ){
      mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    } else {
      mysqli_report(MYSQLI_REPORT_OFF);
    }
  }

  public static function close() : bool {
    if( self::$mysqli !== null ){
      return self::$mysqli->close();
    }
    return true;
  }

  /**
   * All queries go here. Stops the output of multiquery
   *
   * @param string $sql SQL query
   * @return mysqli_result | bool
   * @throws mysqli_sql_exception If any mysqli function failed due to mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)
   */
  public static function q(string $sql) {
    if(!$result = self::mysqli()->query($sql, MYSQLI_STORE_RESULT)){
      error_log('MyDB: Query Error (' . self::mysqli()->errno . ') ' .self::mysqli()->error);
      return false;
    }

    #clear multi-result
    while(self::mysqli()->more_results() && self::mysqli()->next_result()){
      self::mysqli()->store_result();
    }

    return $result;
  }


  /**
   * Fetch one value from mysqli_result
   *
   * @param mysqli_result $query
   * @throws mysqli_sql_exception If any mysqli function failed due to mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)
   */
  public static function o(mysqli_result $query ) {
    if($query->num_rows == 0){
      return null;
    }
    $query->data_seek(0);
    $row = $query->fetch_row();
    $query->free();
    return $row[0];
  }


  /**
   * Fetch one value from sql query
   *
   * @param string $sql SQL query
   * @throws mysqli_sql_exception If any mysqli function failed due to mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)
   */
  public static function oSQL(string $sql) {
    return self::o(self::q($sql));
  }


  /**
   * Return fields array from mysqli_result
   *
   * @param mysqli_result $query
   * @throws mysqli_sql_exception If any mysqli function failed due to mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)
   */
  public static function aFields(mysqli_result $query ) : array {
    $names = [];
    foreach ($query->fetch_fields() as $val ) {
      $names[] = $val->name;
    }
    return $names;
  }


  /**
   * Return html table from mysqli_result
   *
   * @param mysqli_result $query
   * @throws mysqli_sql_exception If any mysqli function failed due to mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)
   */
  public static function html(mysqli_result $query, string $caption = '', string $columns_width = '', string $width = '100%', bool $free = TRUE ) : string {
    $table = "<table width='$width' border='1' style='line-height:1.25rem;border-collapse:collapse;font-size:0.9rem;margin:0.1rem;font-style:serif;'>".PHP_EOL;

    if ($caption <> ''){
      $table .= "<caption style='font-size:1rem;font-weight:bold;text-align:center;'>$caption</caption>".PHP_EOL;
    }

    $table .= $columns_width;
    $flds = self::aFields( $query );
    $table .= '<tr>';
    foreach ($flds as $fld){
      $table .= "<th align='center'>$fld</th>";
    }
    $table .= '</tr>'.PHP_EOL;

    $query->data_seek(0);
    while ($row = $query->fetch_row()) {
      $table .= '<tr>' ;
      foreach ($row as $val){
        $table .= "<td>$val</td>";
      }
      $table .= '</tr>'.PHP_EOL;
    }
    $table.='</table>'.PHP_EOL;

    if ($free) {
      $query->free();
    }

    return $table;
  }


  /**
   * Return array from mysqli_result
   *
   * @param string $sql
   * @param bool $assoc return assoc array or simple array
   * @param bool $free free mysqli_result
   * @throws mysqli_sql_exception If any mysqli function failed due to mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)
   */
  public static function a(mysqli_result $query, bool $assoc = TRUE, bool $free = TRUE) : array {
    $arr = $query->fetch_all($assoc ? MYSQLI_ASSOC : MYSQLI_NUM);

    if($free){
      $query->free();
    }

    return $arr;
  }


  /**
   * Return array from sql query
   *
   * @param string $sql sql query
   * @param bool $assoc return assoc array or simple array
   * @param bool $free free mysqli_result
   * @throws mysqli_sql_exception If any mysqli function failed due to mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)
   */
  public static function aSQL(string $sql, bool $assoc = TRUE, bool $free = TRUE) : array {
    return self::a(self::q($sql), $assoc, $free);
  }


  /**
   * Return array from sql query
   *  Example: SELECT id, value FROM spr ORDER BY id; => array[id] = value
   *
   * @param string $sql
   * @throws mysqli_sql_exception If any mysqli function failed due to mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)
   */
  public static function a2(string $sql) : array {
    $arr = self::aSQL($sql, FALSE);
    return array_combine(array_column($arr, 0), array_column($arr, 1));
  }


  /**
   * Return array from sql multi query
   *
   * @param string $sql
   * @return array mysqli_result
   * @throws mysqli_sql_exception If any mysqli function failed due to mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT)
   */
  public static function qMulti(string $sql) : array {
    $arr = [];
    if (self::mysqli()->multi_query($sql)) {
      do {
        $arr[] = self::mysqli()->store_result();
      } while (self::mysqli()->more_results() && self::mysqli()->next_result());
    }

    return $arr;
  }


  /**
   * Make Prepared Statements, bind parameters and execute
   * @param string $sql statement for prepare
   * @param array $param binded parameters
   * @param string $types types (i/d/s/b) for binded parameter, by default is string
   */
  public static function p(string $sql, array $params = [], string $types = '') : mysqli_stmt {
    $stmt = self::mysqli()->prepare($sql);
    if(count($params) > 0){
      $types = $types ?: str_repeat("s", count($params));
      $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
  }


  /**
   * Returns the result of execution Prepared Statements with binded parameters
   * @param string $sql statement for prepare
   * @param array $param binded parameters
   * @param string $types types (i/d/s/b) for binded parameter, by default is string
   */
  public static function pr(string $sql, array $params = [], string $types = '') {
    $stmt =  self::p($sql, $params, $types);
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
  }


  /**
   * Format value for working with sql
   *
   * @param mixed $value
   * @return string
   */
  public static function t( $value ) : string {
    $data = strval($value);
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return self::mysqli()->real_escape_string($data);
  }


  /**
   * Test and format post value
   *
   * @param string $var_name
   * @param $def_value default value if post value not exists
   */
  public static function tip(string $var_name, $def_value = '') : string {
    $data = $_POST[$var_name] ?? $def_value;
    return self::t($data);
  }


  /**
   * Test and format post value for search fields for working with sql - replace '*' on '%'
   *
   * @param string $var_name
   * @param $def_value default value if post value not exists
   */
  public static function sfp(string $var_name, $def_value = '') : string {
    return str_replace('*', '%', MyDB::tip($var_name, $def_value));
  }


  /**
   * Tests and formats post values for working with sql
   * replaces an empty string with NULL and puts the value in quotes
   *
   * @param string $var_name
   * @param $def_value default value if post value not exists
   * @param bool $quotes puts the value in quotes
   */
  public static function qtip(string $var_name, $def_value = '', bool $quotes = TRUE) : string {
    $data = self::tip($var_name, $def_value);
    if($data == '')
      $data = 'NULL';
    elseif( $quotes )
      $data = "'$data'";
    return $data;
  }
}
