(function ($, Drupal) {

  Drupal.tableDrag.prototype.row.prototype.defaultIsValidSwap = Drupal.tableDrag.prototype.row.prototype.isValidSwap;
  Drupal.tableDrag.prototype.row.prototype.isValidSwap = function(row) {
    var $row = $(row);
    if ($row.is(':not(.draggable)')) {
      return false;
    }
    return this.defaultIsValidSwap(row);
  }

})(jQuery, Drupal);
