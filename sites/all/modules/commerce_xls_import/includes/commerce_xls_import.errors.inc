<?php

/**
 * @file
 * Commerce_xls_import.errors.inc.
 *
 * Error functions.
 */

/**
 * Get error message if it exists.
 *
 * @param int $row
 *   A numerical value representing a row in the current excel spreadsheet.
 *
 * @param string $column
 *  The title of a column in the current excel spreadsheet.
 *
 * @return string $message
 *  The error message description for the given row and column; message is blank
 *  if there are no errors.
 */
function commerce_xls_import_get_error($row, $column) {
  $message = '';

  $result = db_query("SELECT message FROM {commerce_xls_import_errors} WHERE row=:row AND `column`=:column", array(':row' => $row, ':column' => $column))->fetchField();
  if ($result) {
    $message = $result;
  }

  return $message;
}

/**
 * Get whether or not the row has an error.
 *
 * @param int $row
 *  A numerical value representing a row in the current excel spreadsheet.
 *
 * @return boolean
 *  A boolean value representing whether the current row has an error.
 */
function commerce_xls_import_get_row_error($row) {
  $result = db_query('SELECT message FROM {commerce_xls_import_errors} WHERE row=:row', array(':row' => $row))->fetchField();

  if ($result) {
    return TRUE;
  }

  return FALSE;
}

/**
 * Set error.
 *
 * @param int $row
 *  A numerical value representing a row in the current excel spreadsheet.
 *
 * @param string $column
 *  The title of a column in the current excel spreadsheet.
 *
 * @param string $message
 *  The error message for a given row and column.
 */
function commerce_xls_import_set_error($row, $column, $message) {
  db_insert('commerce_xls_import_errors')
    ->fields(array(
      'row' => $row,
      '`column`' => $column,
      'message' => $message,
    ))
    ->execute();
}

/**
 * Gets the columns status.
 *
 * Determines the column status (error, success) for the
 * phpexport export hook to color code warnings and errors.
 *
 * If whole line is good, mark green, else mark white and highlight warnings and
 * errors.
 *
 * @param int $row
 *  A numerical value representing a row in the current excel spreadsheet.
 *
 * @param string $column
 *  The title of a column in the current excel spreadsheet.
 *
 * @return int
 *  A constant representing a status code.
 */
function commerce_xls_import_get_column_status($row, $column) {
  $error = commerce_xls_import_get_error($row, $column);

  if ($error) {
    return COMMERCE_XLS_IMPORT_DATA_ERROR;
  }
  else {
    return COMMERCE_XLS_IMPORT_DATA_SUCCESS;
  }
}

/*
 * Gets the total number of errors for the import, simply counts all the error entries
 */
function commerce_xls_import_count_errors() {
  $total = 0;

  $result = db_query("SELECT count(*) FROM {commerce_xls_import_errors}")->fetchField();
  if ($result) {
    $total = $result;
  }

  return $total;
}
