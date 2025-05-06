YUI.add('moodle-availability_coursecompletion-form', function (Y, NAME) {

/**
 * JavaScript for form editing completion conditions.
 *
 * @module moodle-availability_completion-form
 */
M.availability_coursecompletion = M.availability_coursecompletion || {};

/**
 * @class M.availability_completion.form
 * @extends M.core_availability.plugin
 */
M.availability_coursecompletion.form = Y.Object(M.core_availability.plugin);

/**
 * Courses available for selection (alphabetical order).
 *
 * @property courses
 * @type Array
 */
M.availability_coursecompletion.form.courses = null;

/**
 * Initialises this plugin.
 *
 * @method initInner
 * @param {Array} cms Array of objects containing cmid => name
 */
M.availability_coursecompletion.form.initInner = function(standardFields) {
    this.courses = standardFields;
};

M.availability_coursecompletion.form.getNode = function(json) {
    // Create HTML structure.
    var strings = M.str.availability_coursecompletion;
    var html = strings.title + ' <span class="availability-group"><label>' +
            '<span class="accesshide">' + strings.label_course + ' </span>' +
            '<select name="courseid" title="' + strings.label_course + '">' +
            '<option value="0">' + M.str.moodle.choosedots + '</option>';
    for (var i = 0; i < this.courses.length; i++) {
        fieldInfo = this.courses[i];
        // String has already been escaped using format_string.
        html += '<option value="c_' + fieldInfo.field + '">' + fieldInfo.display + '</option>';
    }
    html += '</select></label></span>';

    var node = Y.Node.create('<span>' + html + '</span>');

    // Set initial values.
    // Set initial values if specified.
    if (json.c !== undefined &&
            node.one('select[name=courseid] > option[value=c_' + json.c + ']')) {
        node.one('select[name=courseid]').set('value', 'c_' + json.c);
    }

    // Add event handlers (first time only).
    if (!M.availability_coursecompletion.form.addedEvents) {
        M.availability_coursecompletion.form.addedEvents = true;
        var root = Y.one('#fitem_id_availabilityconditionsjson');
        root.delegate('change', function() {
            // Whichever dropdown changed, just update the form.
            M.core_availability.form.update();
        }, '.availability_coursecompletion select');
    }

    return node;
};

M.availability_coursecompletion.form.fillValue = function(value, node) {
    var field = node.one('select[name=courseid]').get('value');
    if (field.substr(0, 2) === 'c_') {
        value.c = field.substr(2);
    }
};

M.availability_coursecompletion.form.fillErrors = function(errors, node) {
    var value = {};
    this.fillValue(value, node);

    // Check course
    if (value.c === undefined) {
        errors.push('availability_coursecompletion:error_nocourse');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
