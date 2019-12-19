$.fn.setClass = function(cName) {
    this.each(function() { this.className = cName;});
    return this;
};