<?php
/**
 * Secure SQL query builder class.
 *
 * @package Invitation_Manager_Pro
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * IMPRO_Query_Builder class.
 */
class IMPRO_Query_Builder {

    /**
     * Database instance.
     *
     * @var IMPRO_Database
     */
    private $database;

    /**
     * WordPress database instance.
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Current table name.
     *
     * @var string
     */
    private $table;

    /**
     * SELECT fields.
     *
     * @var array
     */
    private $select = array();

    /**
     * WHERE conditions.
     *
     * @var array
     */
    private $where = array();

    /**
     * JOIN clauses.
     *
     * @var array
     */
    private $joins = array();

    /**
     * ORDER BY clauses.
     *
     * @var array
     */
    private $order_by = array();

    /**
     * GROUP BY clauses.
     *
     * @var array
     */
    private $group_by = array();

    /**
     * HAVING conditions.
     *
     * @var array
     */
    private $having = array();

    /**
     * LIMIT clause.
     *
     * @var int|null
     */
    private $limit = null;

    /**
     * OFFSET clause.
     *
     * @var int|null
     */
    private $offset = null;

    /**
     * Query parameters for prepared statements.
     *
     * @var array
     */
    private $parameters = array();

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->database = new IMPRO_Database();
    }

    /**
     * Set table for query.
     *
     * @param string $table_key Table key from database schema.
     * @return self
     */
    public function table( $table_key ) {
        $this->table = $this->database->get_table_name( $table_key );
        return $this;
    }

    /**
     * Add SELECT fields.
     *
     * @param string|array $fields Fields to select.
     * @return self
     */
    public function select( $fields = '*' ) {
        if ( is_array( $fields ) ) {
            $this->select = array_merge( $this->select, $fields );
        } else {
            $this->select[] = $fields;
        }
        return $this;
    }

    /**
     * Add WHERE condition.
     *
     * @param string $column Column name.
     * @param mixed  $operator Operator or value if using equals.
     * @param mixed  $value Value (optional if operator is the value).
     * @param string $boolean Boolean operator (AND/OR).
     * @return self
     */
    public function where( $column, $operator, $value = null, $boolean = 'AND' ) {
        if ( $value === null ) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = array(
            'column' => $this->sanitize_column_name( $column ),
            'operator' => $this->sanitize_operator( $operator ),
            'value' => $value,
            'boolean' => strtoupper( $boolean )
        );

        $this->parameters[] = $value;
        return $this;
    }

    /**
     * Add WHERE IN condition.
     *
     * @param string $column Column name.
     * @param array  $values Array of values.
     * @param string $boolean Boolean operator (AND/OR).
     * @return self
     */
    public function where_in( $column, $values, $boolean = 'AND' ) {
        if ( ! is_array( $values ) || empty( $values ) ) {
            return $this;
        }

        $placeholders = implode( ',', array_fill( 0, count( $values ), '%s' ) );
        
        $this->where[] = array(
            'column' => $this->sanitize_column_name( $column ),
            'operator' => 'IN',
            'value' => "({$placeholders})",
            'boolean' => strtoupper( $boolean ),
            'raw' => true
        );

        $this->parameters = array_merge( $this->parameters, $values );
        return $this;
    }

    /**
     * Add WHERE NOT IN condition.
     *
     * @param string $column Column name.
     * @param array  $values Array of values.
     * @param string $boolean Boolean operator (AND/OR).
     * @return self
     */
    public function where_not_in( $column, $values, $boolean = 'AND' ) {
        if ( ! is_array( $values ) || empty( $values ) ) {
            return $this;
        }

        $placeholders = implode( ',', array_fill( 0, count( $values ), '%s' ) );
        
        $this->where[] = array(
            'column' => $this->sanitize_column_name( $column ),
            'operator' => 'NOT IN',
            'value' => "({$placeholders})",
            'boolean' => strtoupper( $boolean ),
            'raw' => true
        );

        $this->parameters = array_merge( $this->parameters, $values );
        return $this;
    }

    /**
     * Add WHERE LIKE condition.
     *
     * @param string $column Column name.
     * @param string $value Value to search for.
     * @param string $boolean Boolean operator (AND/OR).
     * @return self
     */
    public function where_like( $column, $value, $boolean = 'AND' ) {
        $this->where[] = array(
            'column' => $this->sanitize_column_name( $column ),
            'operator' => 'LIKE',
            'value' => $value,
            'boolean' => strtoupper( $boolean )
        );

        $this->parameters[] = $value;
        return $this;
    }

    /**
     * Add WHERE BETWEEN condition.
     *
     * @param string $column Column name.
     * @param mixed  $min Minimum value.
     * @param mixed  $max Maximum value.
     * @param string $boolean Boolean operator (AND/OR).
     * @return self
     */
    public function where_between( $column, $min, $max, $boolean = 'AND' ) {
        $this->where[] = array(
            'column' => $this->sanitize_column_name( $column ),
            'operator' => 'BETWEEN',
            'value' => '%s AND %s',
            'boolean' => strtoupper( $boolean ),
            'raw' => true
        );

        $this->parameters[] = $min;
        $this->parameters[] = $max;
        return $this;
    }

    /**
     * Add WHERE NULL condition.
     *
     * @param string $column Column name.
     * @param string $boolean Boolean operator (AND/OR).
     * @return self
     */
    public function where_null( $column, $boolean = 'AND' ) {
        $this->where[] = array(
            'column' => $this->sanitize_column_name( $column ),
            'operator' => 'IS NULL',
            'value' => '',
            'boolean' => strtoupper( $boolean ),
            'raw' => true
        );

        return $this;
    }

    /**
     * Add WHERE NOT NULL condition.
     *
     * @param string $column Column name.
     * @param string $boolean Boolean operator (AND/OR).
     * @return self
     */
    public function where_not_null( $column, $boolean = 'AND' ) {
        $this->where[] = array(
            'column' => $this->sanitize_column_name( $column ),
            'operator' => 'IS NOT NULL',
            'value' => '',
            'boolean' => strtoupper( $boolean ),
            'raw' => true
        );

        return $this;
    }

    /**
     * Add OR WHERE condition.
     *
     * @param string $column Column name.
     * @param mixed  $operator Operator or value.
     * @param mixed  $value Value (optional).
     * @return self
     */
    public function or_where( $column, $operator, $value = null ) {
        return $this->where( $column, $operator, $value, 'OR' );
    }

    /**
     * Add JOIN clause.
     *
     * @param string $table Table to join.
     * @param string $first First column.
     * @param string $operator Operator.
     * @param string $second Second column.
     * @param string $type Join type (INNER, LEFT, RIGHT).
     * @return self
     */
    public function join( $table, $first, $operator, $second, $type = 'INNER' ) {
        $table_name = $this->database->get_table_name( $table );
        
        $this->joins[] = array(
            'type' => strtoupper( $type ),
            'table' => $table_name,
            'first' => $this->sanitize_column_name( $first ),
            'operator' => $this->sanitize_operator( $operator ),
            'second' => $this->sanitize_column_name( $second )
        );

        return $this;
    }

    /**
     * Add LEFT JOIN clause.
     *
     * @param string $table Table to join.
     * @param string $first First column.
     * @param string $operator Operator.
     * @param string $second Second column.
     * @return self
     */
    public function left_join( $table, $first, $operator, $second ) {
        return $this->join( $table, $first, $operator, $second, 'LEFT' );
    }

    /**
     * Add RIGHT JOIN clause.
     *
     * @param string $table Table to join.
     * @param string $first First column.
     * @param string $operator Operator.
     * @param string $second Second column.
     * @return self
     */
    public function right_join( $table, $first, $operator, $second ) {
        return $this->join( $table, $first, $operator, $second, 'RIGHT' );
    }

    /**
     * Add ORDER BY clause.
     *
     * @param string $column Column name.
     * @param string $direction Direction (ASC/DESC).
     * @return self
     */
    public function order_by( $column, $direction = 'ASC' ) {
        $this->order_by[] = array(
            'column' => $this->sanitize_column_name( $column ),
            'direction' => strtoupper( $direction ) === 'DESC' ? 'DESC' : 'ASC'
        );

        return $this;
    }

    /**
     * Add GROUP BY clause.
     *
     * @param string|array $columns Column name(s).
     * @return self
     */
    public function group_by( $columns ) {
        if ( is_array( $columns ) ) {
            foreach ( $columns as $column ) {
                $this->group_by[] = $this->sanitize_column_name( $column );
            }
        } else {
            $this->group_by[] = $this->sanitize_column_name( $columns );
        }

        return $this;
    }

    /**
     * Add HAVING condition.
     *
     * @param string $column Column name.
     * @param mixed  $operator Operator or value.
     * @param mixed  $value Value (optional).
     * @param string $boolean Boolean operator (AND/OR).
     * @return self
     */
    public function having( $column, $operator, $value = null, $boolean = 'AND' ) {
        if ( $value === null ) {
            $value = $operator;
            $operator = '=';
        }

        $this->having[] = array(
            'column' => $this->sanitize_column_name( $column ),
            'operator' => $this->sanitize_operator( $operator ),
            'value' => $value,
            'boolean' => strtoupper( $boolean )
        );

        $this->parameters[] = $value;
        return $this;
    }

    /**
     * Set LIMIT clause.
     *
     * @param int $limit Limit number.
     * @return self
     */
    public function limit( $limit ) {
        $this->limit = max( 0, intval( $limit ) );
        return $this;
    }

    /**
     * Set OFFSET clause.
     *
     * @param int $offset Offset number.
     * @return self
     */
    public function offset( $offset ) {
        $this->offset = max( 0, intval( $offset ) );
        return $this;
    }

    /**
     * Execute SELECT query and get results.
     *
     * @param bool $use_cache Whether to use cache.
     * @return array Query results.
     */
    public function get( $use_cache = true ) {
        $sql = $this->build_select_query();
        
        if ( $use_cache ) {
            $cache_key = IMPRO_Cache::get_query_cache_key( $sql, $this->parameters );
            $cached_result = IMPRO_Cache::get_cached_query_result( $sql, $this->parameters );
            
            if ( $cached_result !== false ) {
                $this->reset();
                return $cached_result;
            }
        }

        $prepared_sql = $this->wpdb->prepare( $sql, $this->parameters );
        $results = $this->wpdb->get_results( $prepared_sql );

        if ( $use_cache && $results !== false ) {
            IMPRO_Cache::cache_query_result( $sql, $this->parameters, $results );
        }

        $this->reset();
        return $results ?: array();
    }

    /**
     * Execute SELECT query and get first result.
     *
     * @param bool $use_cache Whether to use cache.
     * @return object|null First result or null.
     */
    public function first( $use_cache = true ) {
        $this->limit( 1 );
        $results = $this->get( $use_cache );
        return ! empty( $results ) ? $results[0] : null;
    }

    /**
     * Execute COUNT query.
     *
     * @param string $column Column to count (default: *).
     * @param bool   $use_cache Whether to use cache.
     * @return int Count result.
     */
    public function count( $column = '*', $use_cache = true ) {
        $this->select = array( "COUNT({$column}) as count" );
        $result = $this->first( $use_cache );
        return $result ? intval( $result->count ) : 0;
    }

    /**
     * Execute SUM query.
     *
     * @param string $column Column to sum.
     * @param bool   $use_cache Whether to use cache.
     * @return float Sum result.
     */
    public function sum( $column, $use_cache = true ) {
        $this->select = array( "SUM({$this->sanitize_column_name($column)}) as sum" );
        $result = $this->first( $use_cache );
        return $result ? floatval( $result->sum ) : 0;
    }

    /**
     * Execute AVG query.
     *
     * @param string $column Column to average.
     * @param bool   $use_cache Whether to use cache.
     * @return float Average result.
     */
    public function avg( $column, $use_cache = true ) {
        $this->select = array( "AVG({$this->sanitize_column_name($column)}) as avg" );
        $result = $this->first( $use_cache );
        return $result ? floatval( $result->avg ) : 0;
    }

    /**
     * Execute MAX query.
     *
     * @param string $column Column to get max.
     * @param bool   $use_cache Whether to use cache.
     * @return mixed Max result.
     */
    public function max( $column, $use_cache = true ) {
        $this->select = array( "MAX({$this->sanitize_column_name($column)}) as max" );
        $result = $this->first( $use_cache );
        return $result ? $result->max : null;
    }

    /**
     * Execute MIN query.
     *
     * @param string $column Column to get min.
     * @param bool   $use_cache Whether to use cache.
     * @return mixed Min result.
     */
    public function min( $column, $use_cache = true ) {
        $this->select = array( "MIN({$this->sanitize_column_name($column)}) as min" );
        $result = $this->first( $use_cache );
        return $result ? $result->min : null;
    }

    /**
     * Execute INSERT query.
     *
     * @param array $data Data to insert.
     * @return int|false Insert ID or false on failure.
     */
    public function insert( $data ) {
        if ( empty( $data ) || ! $this->table ) {
            return false;
        }

        $columns = array_keys( $data );
        $values = array_values( $data );
        $placeholders = array_fill( 0, count( $values ), '%s' );

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode( ', ', array_map( array( $this, 'sanitize_column_name' ), $columns ) ),
            implode( ', ', $placeholders )
        );

        $prepared_sql = $this->wpdb->prepare( $sql, $values );
        $result = $this->wpdb->query( $prepared_sql );

        $this->reset();

        if ( $result !== false ) {
            // Invalidate related cache
            $this->invalidate_table_cache();
            return $this->wpdb->insert_id;
        }

        return false;
    }

    /**
     * Execute batch INSERT query.
     *
     * @param array $data Array of data arrays to insert.
     * @return int|false Number of inserted rows or false on failure.
     */
    public function insert_batch( $data ) {
        if ( empty( $data ) || ! $this->table ) {
            return false;
        }

        $first_row = reset( $data );
        $columns = array_keys( $first_row );
        $column_count = count( $columns );

        $values_sql = array();
        $parameters = array();

        foreach ( $data as $row ) {
            $row_values = array();
            foreach ( $columns as $column ) {
                $row_values[] = '%s';
                $parameters[] = $row[ $column ] ?? null;
            }
            $values_sql[] = '(' . implode( ', ', $row_values ) . ')';
        }

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES %s",
            $this->table,
            implode( ', ', array_map( array( $this, 'sanitize_column_name' ), $columns ) ),
            implode( ', ', $values_sql )
        );

        $prepared_sql = $this->wpdb->prepare( $sql, $parameters );
        $result = $this->wpdb->query( $prepared_sql );

        $this->reset();

        if ( $result !== false ) {
            // Invalidate related cache
            $this->invalidate_table_cache();
            return $result;
        }

        return false;
    }

    /**
     * Execute UPDATE query.
     *
     * @param array $data Data to update.
     * @return int|false Number of affected rows or false on failure.
     */
    public function update( $data ) {
        if ( empty( $data ) || ! $this->table || empty( $this->where ) ) {
            return false;
        }

        $set_clauses = array();
        $parameters = array();

        foreach ( $data as $column => $value ) {
            $set_clauses[] = $this->sanitize_column_name( $column ) . ' = %s';
            $parameters[] = $value;
        }

        $sql = sprintf(
            "UPDATE %s SET %s",
            $this->table,
            implode( ', ', $set_clauses )
        );

        // Add WHERE clause
        if ( ! empty( $this->where ) ) {
            $sql .= ' WHERE ' . $this->build_where_clause();
            $parameters = array_merge( $parameters, $this->parameters );
        }

        $prepared_sql = $this->wpdb->prepare( $sql, $parameters );
        $result = $this->wpdb->query( $prepared_sql );

        $this->reset();

        if ( $result !== false ) {
            // Invalidate related cache
            $this->invalidate_table_cache();
            return $result;
        }

        return false;
    }

    /**
     * Execute DELETE query.
     *
     * @return int|false Number of affected rows or false on failure.
     */
    public function delete() {
        if ( ! $this->table || empty( $this->where ) ) {
            return false;
        }

        $sql = sprintf( "DELETE FROM %s", $this->table );

        // Add WHERE clause
        if ( ! empty( $this->where ) ) {
            $sql .= ' WHERE ' . $this->build_where_clause();
        }

        $prepared_sql = $this->wpdb->prepare( $sql, $this->parameters );
        $result = $this->wpdb->query( $prepared_sql );

        $this->reset();

        if ( $result !== false ) {
            // Invalidate related cache
            $this->invalidate_table_cache();
            return $result;
        }

        return false;
    }

    /**
     * Build SELECT query.
     *
     * @return string SQL query.
     */
    private function build_select_query() {
        $sql = 'SELECT ';
        
        // SELECT clause
        if ( empty( $this->select ) ) {
            $sql .= '*';
        } else {
            $sql .= implode( ', ', $this->select );
        }

        // FROM clause
        $sql .= " FROM {$this->table}";

        // JOIN clauses
        if ( ! empty( $this->joins ) ) {
            foreach ( $this->joins as $join ) {
                $sql .= sprintf(
                    " %s JOIN %s ON %s %s %s",
                    $join['type'],
                    $join['table'],
                    $join['first'],
                    $join['operator'],
                    $join['second']
                );
            }
        }

        // WHERE clause
        if ( ! empty( $this->where ) ) {
            $sql .= ' WHERE ' . $this->build_where_clause();
        }

        // GROUP BY clause
        if ( ! empty( $this->group_by ) ) {
            $sql .= ' GROUP BY ' . implode( ', ', $this->group_by );
        }

        // HAVING clause
        if ( ! empty( $this->having ) ) {
            $sql .= ' HAVING ' . $this->build_having_clause();
        }

        // ORDER BY clause
        if ( ! empty( $this->order_by ) ) {
            $order_clauses = array();
            foreach ( $this->order_by as $order ) {
                $order_clauses[] = $order['column'] . ' ' . $order['direction'];
            }
            $sql .= ' ORDER BY ' . implode( ', ', $order_clauses );
        }

        // LIMIT clause
        if ( $this->limit !== null ) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        // OFFSET clause
        if ( $this->offset !== null ) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    /**
     * Build WHERE clause.
     *
     * @return string WHERE clause.
     */
    private function build_where_clause() {
        $clauses = array();
        
        foreach ( $this->where as $index => $condition ) {
            $clause = '';
            
            if ( $index > 0 ) {
                $clause .= $condition['boolean'] . ' ';
            }
            
            if ( isset( $condition['raw'] ) && $condition['raw'] ) {
                $clause .= $condition['column'] . ' ' . $condition['operator'];
                if ( ! empty( $condition['value'] ) ) {
                    $clause .= ' ' . $condition['value'];
                }
            } else {
                $clause .= $condition['column'] . ' ' . $condition['operator'] . ' %s';
            }
            
            $clauses[] = $clause;
        }

        return implode( ' ', $clauses );
    }

    /**
     * Build HAVING clause.
     *
     * @return string HAVING clause.
     */
    private function build_having_clause() {
        $clauses = array();
        
        foreach ( $this->having as $index => $condition ) {
            $clause = '';
            
            if ( $index > 0 ) {
                $clause .= $condition['boolean'] . ' ';
            }
            
            $clause .= $condition['column'] . ' ' . $condition['operator'] . ' %s';
            $clauses[] = $clause;
        }

        return implode( ' ', $clauses );
    }

    /**
     * Sanitize column name.
     *
     * @param string $column Column name.
     * @return string Sanitized column name.
     */
    private function sanitize_column_name( $column ) {
        // Allow table.column format
        if ( strpos( $column, '.' ) !== false ) {
            $parts = explode( '.', $column );
            return implode( '.', array_map( array( $this, 'sanitize_single_column' ), $parts ) );
        }
        
        return $this->sanitize_single_column( $column );
    }

    /**
     * Sanitize single column name.
     *
     * @param string $column Column name.
     * @return string Sanitized column name.
     */
    private function sanitize_single_column( $column ) {
        // Allow functions and aliases
        if ( preg_match( '/^[a-zA-Z_][a-zA-Z0-9_]*(\([^)]*\))?(\s+AS\s+[a-zA-Z_][a-zA-Z0-9_]*)?$/i', $column ) ) {
            return $column;
        }
        
        // Basic column name
        return preg_replace( '/[^a-zA-Z0-9_]/', '', $column );
    }

    /**
     * Sanitize operator.
     *
     * @param string $operator Operator.
     * @return string Sanitized operator.
     */
    private function sanitize_operator( $operator ) {
        $allowed_operators = array(
            '=', '!=', '<>', '<', '>', '<=', '>=',
            'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
            'BETWEEN', 'NOT BETWEEN', 'IS NULL', 'IS NOT NULL'
        );

        $operator = strtoupper( trim( $operator ) );
        
        if ( in_array( $operator, $allowed_operators ) ) {
            return $operator;
        }

        return '='; // Default to equals
    }

    /**
     * Reset query builder state.
     */
    private function reset() {
        $this->table = null;
        $this->select = array();
        $this->where = array();
        $this->joins = array();
        $this->order_by = array();
        $this->group_by = array();
        $this->having = array();
        $this->limit = null;
        $this->offset = null;
        $this->parameters = array();
    }

    /**
     * Invalidate table cache.
     */
    private function invalidate_table_cache() {
        if ( $this->table ) {
            // Determine cache group based on table name
            $table_key = str_replace( $this->wpdb->prefix . 'impro_', '', $this->table );
            $cache_group = $table_key;
            
            IMPRO_Cache::flush_group( $cache_group );
        }
    }

    /**
     * Get raw SQL query for debugging.
     *
     * @return string Raw SQL query.
     */
    public function to_sql() {
        $sql = $this->build_select_query();
        
        if ( ! empty( $this->parameters ) ) {
            $sql = $this->wpdb->prepare( $sql, $this->parameters );
        }
        
        return $sql;
    }

    /**
     * Execute raw SQL query.
     *
     * @param string $sql SQL query.
     * @param array  $parameters Query parameters.
     * @return mixed Query result.
     */
    public function raw( $sql, $parameters = array() ) {
        if ( ! empty( $parameters ) ) {
            $sql = $this->wpdb->prepare( $sql, $parameters );
        }
        
        return $this->wpdb->get_results( $sql );
    }

    /**
     * Start database transaction.
     *
     * @return bool True on success, false on failure.
     */
    public function begin_transaction() {
        return $this->wpdb->query( 'START TRANSACTION' ) !== false;
    }

    /**
     * Commit database transaction.
     *
     * @return bool True on success, false on failure.
     */
    public function commit() {
        return $this->wpdb->query( 'COMMIT' ) !== false;
    }

    /**
     * Rollback database transaction.
     *
     * @return bool True on success, false on failure.
     */
    public function rollback() {
        return $this->wpdb->query( 'ROLLBACK' ) !== false;
    }

    /**
     * Execute query within transaction.
     *
     * @param callable $callback Callback function to execute.
     * @return mixed Callback result or false on failure.
     */
    public function transaction( $callback ) {
        if ( ! is_callable( $callback ) ) {
            return false;
        }

        $this->begin_transaction();

        try {
            $result = call_user_func( $callback, $this );
            $this->commit();
            return $result;
        } catch ( Exception $e ) {
            $this->rollback();
            error_log( 'Database transaction failed: ' . $e->getMessage() );
            return false;
        }
    }
}

