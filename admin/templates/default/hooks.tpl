{include 'grid.tpl'}
<hr>
<div class="wrap-list" style="display: none;">
    <div class="wrap-group">
        <textarea name="code" id="codeContainer" data-type="php" rows="20" cols="100" style="width: 100%;"></textarea>
    </div>

    <div class="form-actions inline">
        <input type="submit" class="btn btn-success" value="{lang key='save'}" id="js-save-cmd">
        <input type="submit" class="btn btn-danger" value="{lang key='close_all'}" id="js-close-cmd">
    </div>
</div>
{ia_print_js files='utils/edit_area/edit_area'}