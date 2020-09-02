# MyDB
Simple and power wrapper for mysqli PHP 

### Simple
All functionality is implemented statically.To work with an instance is used the analog singleton pattern. But you don't need to create a instance of the connection specifically, everything happens automatically))

### Power
The main functions are implemented in the class itself, but nothing prevents you from using all the features of mysqli
```php
MyDB::mysqli()->func_name(...)
```

### Functions
1. mysqli - return the instance of mysqli
2. rep - enables or disables internal reporting functions
3. close - closing the mysqli connection
#### query
4. q - executing mysql queries
5. o - getting a single value from mysqli_result. Used, for example, to get the result of executing an aggregate function
6. oSQL - getting a single value from the result of executing an sql query
7. aFields - an array of fields mysqli_result
8. html - returns mysqli_result as an html table
9. a - returns mysqli_result as an array
10. aSQL - returns the result of executing an sql query as an array
11. a2 - returns the result of executing an sql query as a two-dimensional associative array.
    For example, SELECT id, value FROM spr ORDER BY id; = > array[id] = value
#### multi query
12. qMulti - returns the mysqli_result array obtained as a result of executing the multiquery
#### prepared statements
13. p - make Prepared Statements, bind parameters and execute
14. pr - returns the result of execution Prepared Statements with binded parameters
#### test values
15. t - formating/testing the value of a variable/string for working with sql
16. tip - "test input post" - testing/fomating and set the default value of a variable from the $_POST array
17. spf - "search post values format" - test and format post value for search fields for working with sql - replace '*' on '%'
18. qtip - test and format post values for working with sql replaces an empty string with NULL and puts the value in quotes

### Configuration
You must first define the values DB_HOST, DB_USER, DB_PASS, DB_NAME and if you need DB_CHAR
> OR you have to modify the DB::mysqli() method

## Example
```php
MyDB::q('DROP TABLE IF EXISTS TEST;');

$query = 'CREATE TABLE `TEST` (
  `idTEST` int(11) NOT NULL AUTO_INCREMENT,
  `A` varchar(45) DEFAULT NULL,
  `C` varchar(45) DEFAULT NULL,
  `D` varchar(45) DEFAULT NULL,
  `E` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idTEST`)
);';

MyDB::q($query);

$query = 'INSERT INTO `TEST` (A,C,D,E) VALUES(1,2,3,4),(5,9,"AAA","SSS"),("BBBB","290674", "JJJJJJJ","Asddddd");';
MyDB::q($query);

$query = 'INSERT INTO `TEST` (A,C,D,E) VALUES(?,?,?,?);';
MyDB::pr($query, [9,8,11,15], 'iiii');
MyDB::pr($query, [12,6,'Ds','HS'], 'iiss');

MyDB::pr('INSERT INTO `TEST` (A,C,D,E) VALUES(?,?,?,?);', [12,6,'Ds','HS'], 'iiss');

echo MyDB::html(MyDB::q("SELECT * FROM TEST;"), "TEST MyDB") . "<br>";

var_dump(MyDB::aSQL("SELECT * FROM TEST ORDER BY 1 DESC;"));

echo "<br><br>";

var_dump(MyDB::oSQL("SELECT count(*) FROM TEST;"));

echo "<br><br>";

var_dump(MyDB::a2("SELECT idTEST, E FROM TEST;"));

MyDB::close();
```
