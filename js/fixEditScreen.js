/* javascript tied to the tpl/edit.html screen
 * a workaround for the jumping to the top behaviour
 * Currently, focus is shifting to an element called input-number
 * which has a style attribute which puts it at the top of the screen
 * <input class="floating-element" type="number" id="input-number" style="left: -666px; top: -666px;">
 */
function frameLoaded() {
document.getElementById("input-number").style["left"] = 0;
document.getElementById("input-number").style["top"] = 0;
};
