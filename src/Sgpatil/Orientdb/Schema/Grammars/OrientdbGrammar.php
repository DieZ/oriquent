<?php

namespace Sgpatil\Orientdb\Schema\Grammars;

use Sgpatil\Orientdb\Connection;
use Illuminate\Support\Fluent;
use Sgpatil\Orientdb\Schema\Blueprint;

class OrientdbGrammar extends Grammar {

    /**
     * The possible column modifiers.
     *
     * @var array
     */
    //protected $modifiers = array('Unsigned', 'Nullable', 'Default', 'Increment', 'Comment', 'After');
    protected $modifiers = array("Linkedclass", "Linkedtype", "Min", "Mandatory", "Max", "Name", "Notnull", "Regex", "Type", "Collate", "Readonly", "Custom", "Default");
    
    /**
     * The possible column serials
     *
     * @var array
     */
    protected $serials = array('bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger');

    /**
     * Compile the query to determine the list of tables.
     *
     * @return string
     */
    public function compileTableExists($table) {
        return 'select * from ' . $table;
    }

    /**
     * Compile the query to determine the list of columns.
     *
     * @return string
     */
    public function compileColumnExists() {
        return "select column_name from information_schema.columns where table_schema = ? and table_name = ?";
    }

    /**
     * Compile a create table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command, Connection $connection) {
       
        // $columns = implode(', ', $this->getColumns($blueprint));
        //$sql = 'create class '.$this->wrapTable($blueprint)." ($columns)";
        
        $createsql = array();
        $createsql[] = 'create class '.$this->wrapTable($blueprint).$this->extendsFrom($blueprint);
        
        // add columns
        $createsql[] = $this->compileAdd($blueprint, $command);
        
        return implode(";", $createsql);
    }
    
    public function extendsFrom($blueprint) {
        $ext = $blueprint->getExtendsFrom();
        return $ext ? " extends {$ext}" : "";
    }
    
    /**
     * Compile a delete table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  \Illuminate\Database\Connection  $connection
     * @return string
     */
    public function compileDelete(Blueprint $blueprint, Fluent $command, Connection $connection) {
       
        // $columns = implode(', ', $this->getColumns($blueprint));
        //$sql = 'create class '.$this->wrapTable($blueprint)." ($columns)";
        return  $sql = 'DELETE VERTEX '.$this->wrapTable($blueprint);
    }

    /**
     * Append the character set specifications to a command.
     *
     * @param  string  $sql
     * @param  \Illuminate\Database\Connection  $connection
     * @return string
     */
    protected function compileCreateEncoding($sql, Connection $connection) {
        if (!is_null($charset = $connection->getConfig('charset'))) {
            $sql .= ' default character set ' . $charset;
        }

        if (!is_null($collation = $connection->getConfig('collation'))) {
            $sql .= ' collate ' . $collation;
        }

        return $sql;
    }

    /**
     * Compile an add column command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command) {
        $table = $this->wrapTable($blueprint);

        //$columns = $this->prefixArray('add', $this->getColumns($blueprint));
        $columns = $this->getColumns($blueprint);
        
        $sql = array();
        
        foreach ($columns as $column) {
            // create a statement for each column
            $sql[] = 'CREATE PROPERTY '.$this->wrapTable($blueprint).'.'.$column;
        }
        
        // @TODO: this one may also not work; depreated syntax
        //CREATE PROPERTY User.name STRING (MANDATORY TRUE, MIN 5, MAX 25)
        //return 'alter class ' . $table . ' ' . implode(', ', $columns);
        return implode(';',$sql);
    }

    /**
     * Compile a primary key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compilePrimary(Blueprint $blueprint, Fluent $command) {
        $command->name(null);

        return $this->compileKey($blueprint, $command, 'primary key');
    }

    /**
     * Compile a unique key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileUnique(Blueprint $blueprint, Fluent $command) {
        return $this->compileKey($blueprint, $command, 'unique');
    }

    /**
     * Compile a plain index key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileIndex(Blueprint $blueprint, Fluent $command) {
        return $this->compileKey($blueprint, $command, 'index');
    }

    /**
     * Compile an index creation command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @param  string  $type
     * @return string
     */
    protected function compileKey(Blueprint $blueprint, Fluent $command, $type) {
        $columns = $this->columnize($command->columns);

        $table = $this->wrapTable($blueprint);

        $sql =  "CREATE INDEX {$command->index} ON {$table} ($columns) {$type}";

        return $sql;
        //return "alter table {$table} add {$type} {$command->index}($columns)";
    }

    /**
     * Compile a drop table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDrop(Blueprint $blueprint, Fluent $command) {
        return 'drop class ' . $this->wrapTable($blueprint) . ' unsafe';
    }

    /**
     * Compile a drop table (if exists) command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIfExists(Blueprint $blueprint, Fluent $command) {
        return 'drop class ' . $this->wrapTable($blueprint) . ' if exists unsafe';
    }

    /**
     * Compile a drop column command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropColumn(Blueprint $blueprint, Fluent $command) {
        $table  = $this->wrapTable($blueprint);
        // @TODO: always one column; or more?
        $column = $command->columns[0];

        // @TODO: changed syntax; is there a better writing
        return 'DROP PROPERTY ' . $table . '.' . $column . ' IF EXISTS';
    }

    /**
     * Compile a drop primary key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropPrimary(Blueprint $blueprint, Fluent $command) {
        return 'alter table ' . $this->wrapTable($blueprint) . ' drop primary key';
    }

    /**
     * Compile a drop unique key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropUnique(Blueprint $blueprint, Fluent $command) {
        $table = $this->wrapTable($blueprint);

        return "alter table {$table} drop index {$command->index}";
    }

    /**
     * Compile a drop index command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropIndex(Blueprint $blueprint, Fluent $command) {
        $table = $this->wrapTable($blueprint);

        return "alter table {$table} drop index {$command->index}";
    }

    /**
     * Compile a drop foreign key command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileDropForeign(Blueprint $blueprint, Fluent $command) {
        $table = $this->wrapTable($blueprint);

        return "alter table {$table} drop foreign key {$command->index}";
    }

    /**
     * Compile a rename table command.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $command
     * @return string
     */
    public function compileRename(Blueprint $blueprint, Fluent $command) {
        $from = $this->wrapTable($blueprint);

        return "rename table {$from} to " . $this->wrapTable($command->to);
    }

    /**
     * Create the column definition for an ANY type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeAny(Fluent $column) {
        return "ANY";
    }
    
    /**
     * Create the column definition for a char type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeChar(Fluent $column) {
        return "STRING"; // TODO: MV not right, check all types!
    }

    /**
     * Create the column definition for a string type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeString(Fluent $column) {
        //return "varchar({$column->length})";
        $q = "STRING";
        /*$constrains = array(); // TODO: MV set a generic function for constrains
        if ($column->length) {
            $constrains[] = "MAX " . $column->length;
        }
        if (count($constrains) > 0) {
            $q .= "(" . implode(", ", $constrains) . ")";
        }*/
        return $q;
    }

    /**
     * Create the column definition for a text type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeText(Fluent $column) {
        return 'STRING';
    }

    /**
     * Create the column definition for a medium text type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMediumText(Fluent $column) {
        return 'STRING';
    }

    /**
     * Create the column definition for a long text type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeLongText(Fluent $column) {
        return 'STRING';
    }

    /**
     * Create the column definition for a big integer type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeBigInteger(Fluent $column) {
        return 'LONG';
    }

    /**
     * Create the column definition for a integer type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeInteger(Fluent $column) {
        return 'INTEGER';
    }

    /**
     * Create the column definition for a medium integer type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeMediumInteger(Fluent $column) {
        return 'LONG';
    }

    /**
     * Create the column definition for a tiny integer type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTinyInteger(Fluent $column) {
        return 'SHORT';
    }

    /**
     * Create the column definition for a small integer type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeSmallInteger(Fluent $column) {
        return 'SHORT';
    }

    /**
     * Create the column definition for a float type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeFloat(Fluent $column) {
        return "FLOAT";
    }

    /**
     * Create the column definition for a double type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDouble(Fluent $column) {
        /*if ($column->total && $column->places) {
            return "double({$column->total}, {$column->places})";
        }*/

        return 'DOUBLE';
    }

    /**
     * Create the column definition for a decimal type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDecimal(Fluent $column) {
        return "DECIMAL";
    }

    /**
     * Create the column definition for a boolean type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeBoolean(Fluent $column) {
        return 'BOOLEAN';
    }

    /**
     * Create the column definition for an enum type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeEnum(Fluent $column) {
        return "STRING"; // TODO: CHECK!
    }

    /**
     * Create the column definition for a date type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDate(Fluent $column) {
        return 'DATE';
    }

    /**
     * Create the column definition for a date-time type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeDateTime(Fluent $column) {
        return 'DATETIME';
    }

    /**
     * Create the column definition for a time type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTime(Fluent $column) {
        return 'DATETIME';
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeTimestamp(Fluent $column) {
       // if (!$column->nullable)
            //return 'timestamp default 0';

        return 'DATETIME';
    }

    /**
     * Create the column definition for a binary type.
     *
     * @param  \Illuminate\Support\Fluent  $column
     * @return string
     */
    protected function typeBinary(Fluent $column) {
        return 'BINARY';
    }

    /**
     * Get the SQL for an unsigned column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyUnsigned(Blueprint $blueprint, Fluent $column) {
        return; // TODO: MV Unsigned mod does not exist in orientDB, raise warning?
        /*if ($column->unsigned)
            return ' unsigned';*/
    }

    /**
     * Get the SQL for a nullable column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyNullable(Blueprint $blueprint, Fluent $column) {
        return $this->modifyNotnull($blueprint, $column);
    }
    
    /**
     * Get the SQL for a nullable column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyNotnull(Blueprint $blueprint, Fluent $column) {
        if ($column->get('nullable',null) !== null) {
            return 'NOTNULL '.$column->nullable;
        }
        elseif ($column->get('notnull',null) !== null) {
            return 'NOTNULL '.$column->notnull;
        }
        return 'NOTNULL TRUE';
    }

    /**
     * Get the SQL for a default column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyDefault(Blueprint $blueprint, Fluent $column) {
        if (!is_null($column->default)) {
            return "DEFAULT " . $this->getDefaultValue($column->default);
        }
    }

    /**
     * Get the SQL for an auto-increment column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyIncrement(Blueprint $blueprint, Fluent $column) {
        return; // TODO: MV Increment mod does not exist in orientDB, raise warning?
        /*if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            return ' auto_increment primary key';
        }*/
    }

    /**
     * Get the SQL for an "after" column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyAfter(Blueprint $blueprint, Fluent $column) {
        return; // TODO: MV After mod does not exist in orientDB, raise warning?
        /*if (!is_null($column->after)) {
            return ' after ' . $this->wrap($column->after);
        }*/
    }

    /**
     * Get the SQL for an "comment" column modifier.
     *
     * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
     * @param  \Illuminate\Support\Fluent  $column
     * @return string|null
     */
    protected function modifyComment(Blueprint $blueprint, Fluent $column) {
        return; // TODO: MV Comment mod does not exist in orientDB, raise warning?
        /*if (!is_null($column->comment)) {
            return ' comment "' . $column->comment . '"';
        }*/
    }
    
    protected function modifyCollate(Blueprint $blueprint, Fluent $column) {
        // TODO: MV implement
    }
    
    protected function modifyCustom(Blueprint $blueprint, Fluent $column) {
        // TODO: MV implement
    }
    
    protected function modifyLinkedclass(Blueprint $blueprint, Fluent $column) {
        // TODO: MV implement
    }
    
    protected function modifyLinkedtype(Blueprint $blueprint, Fluent $column) {
        // TODO: MV implement
    }
    
    protected function modifyMandatory(Blueprint $blueprint, Fluent $column) {
        if (!is_null($column->mandatory)) {
            return 'MANDATORY ' . $this->toQueryValue($column->mandatory);;
        }
    }
    
    protected function modifyMax(Blueprint $blueprint, Fluent $column) {
        if (!is_null($column->max)) {
            return 'MAX ' . (int) $column->max;
        }
    }
    
    protected function modifyMin(Blueprint $blueprint, Fluent $column) {
        if (!is_null($column->min)) {
            return 'MIN ' . (int) $column->min;
        }
    }
    
    protected function modifyName(Blueprint $blueprint, Fluent $column) {
        // TODO: MV implement
    }
    
    protected function modifyReadonly(Blueprint $blueprint, Fluent $column) {
        if (!is_null($column->readonly)) {
            return 'READONLY '.$this->toQueryValue($column->readonly);;
        }
    }
    
    protected function modifyRegex(Blueprint $blueprint, Fluent $column) {
        if (!is_null($column->regex)) {
            return 'REGEX '.$this->toQueryValue($column->regex);
        }
    }
    
    protected function modifyType(Blueprint $blueprint, Fluent $column) {
        // TODO: MV implement
    }
    
    private function toQueryValue($value) {
        if (is_bool($value)) {
            return $value ? "TRUE" : "FALSE";
        }
        if (strtolower($value) == "null") {
            return "NULL";
        }
        return '"'.$value.'"';
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value) {
        if ($value === '*')
            return $value;
        return $value;
        return '`' . str_replace('`', '``', $value) . '`';
    }
    
    	/**
	 * Add the column modifiers to the definition.
         * Overwritten from parent Grammer
	 *
	 * @param  string  $sql
	 * @param  \Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  \Illuminate\Support\Fluent  $column
	 * @return string
	 */
	protected function addModifiers($sql, Blueprint $blueprint, Fluent $column)
	{
		$modifiers = array();
                foreach ($this->modifiers as $modifier)
		{
			if (method_exists($this, $method = "modify{$modifier}"))
			{
				$result = $this->{$method}($blueprint, $column);
                                if ($result) $modifiers[] = $result;
			}
		}
                
                if (count($modifiers) > 0) {
                    $sql.= " (".implode(", ",$modifiers).")";
                }

		return $sql;
	}
        
        /**
	 * Format a value so that it can be used in "default" clauses.
         * Overwritten from parent
	 *
	 * @param  mixed   $value
	 * @return string
	 */
	protected function getDefaultValue($value)
	{
		//if (is_bool($value)) return "'".(int) $value."'";
                if (is_bool($value)) return $value ? "TRUE" : "FALSE";
                
                // else return what parent does:
                return parent::getDefaultValue($value);
	}

}
