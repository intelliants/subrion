{* temporarily hidden
    <a href="#" class="sb-slide js-vm-config-toggle vm-config__toggle"></a>
*}

<input type="hidden" id="js-config-admin-page" value="{$core.config.admin_page}">

<div class="sb-slide vm-bar">
    <div class="vm-bar__title">Visual mode</div>
    <a class="vm-bar__exit" href="?manage_exit=y" title="{lang key='exit'}"><span class="v-icon v-icon--exit"></span> {lang key='exit'}</a>
</div>

<div class="sb-slidebar sb-left">
    <div class="vm-config">
        <div class="vm-spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>

        <input type="hidden" id="js-object" value="">

        <div class="vm-config__item vm-config__item--hidden vm-config__item--position" data-name="">
            <div class="vm-config__item__title"><span class="v-icon v-icon--columns"></span> {lang key='edit_position'}: <b></b></div>

            <form action="" class="vm-form">
                <div class="vm-control-group">
                    <label class="vm-control-label" for="pos-visible-on-page">
                        {lang key='show_on_this_page'}
                    </label>

                    <div class="vm-controls">
                        <div class="vm-checkbox">
                            <i class="v-icon v-icon--square"></i>
                            <input type="checkbox" id="pos-visible-on-page" name="pos-visible-on-page">
                        </div>
                    </div>
                </div>

                <div class="vm-control-group">
                    <label class="vm-control-label" for="pos-visible-everywhere">
                        {lang key='show_on_all_pages'}
                    </label>

                    <div class="vm-controls">
                        <select class="vm-select input-block-level" id="pos-visible-everywhere" name="pos-visible-everywhere">
                            <option value="1">{lang key='show_everywhere'}</option>
                            <option value="0">{lang key='hide_everywhere'}</option>
                        </select>
                    </div>
                </div>
                <div class="vm-actions">
                    <button type="submit" class="js-config-save vm-btn" data-type="positions">{lang key='save'}</button>
                    <button type="reset" class="js-config-close vm-btn pull-right">{lang key='cancel'}</button>
                </div>
            </form>
        </div>

        <div class="vm-config__item vm-config__item--hidden vm-config__item--block">
            <div class="vm-config__item__title"><span class="v-icon v-icon--th-large"></span> {lang key='edit_block'}: <b></b></div>

            <form action="" class="vm-form">
                <div class="vm-control-group">
                    <label class="vm-control-label" for="block-visible-on-page">
                        {lang key='show_on_this_page'}
                    </label>

                    <div class="vm-controls">
                        <div class="vm-checkbox">
                            <i class="v-icon v-icon--square"></i>
                            <input type="checkbox" id="block-visible-on-page" name="block-visible-on-page">
                        </div>
                    </div>
                </div>

                <div class="vm-control-group">
                    <label class="vm-control-label" for="block-visible-everywhere">
                        {lang key='show_on_all_pages'}
                    </label>

                    <div class="vm-controls">
                        <select class="vm-select input-block-level" id="block-visible-everywhere" name="block-visible-everywhere">
                            <option value="1">{lang key='show_everywhere'}</option>
                            <option value="0">{lang key='hide_everywhere'}</option>
                        </select>
                    </div>
                </div>

                {*
                <div class="vm-control-group">
                    <label class="vm-control-label" for="">
                        Classname
                    </label>

                    <div class="vm-controls">
                        <input class="vm-input input-block-level" type="text" name="" id="" value="">
                    </div>
                </div>
                *}

                <div class="vm-actions">
                    <button type="submit" class="js-config-save vm-btn" data-type="blocks">{lang key='save'}</button>
                    <button type="reset" class="js-config-close vm-btn pull-right">{lang key='cancel'}</button>
                </div>
            </form>
        </div>

        {* temporarily hidden
            <div class="vm-config__item vm-config__item--help">
                <div class="vm-config__item__title">Information</div>

                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nisi placeat, odit? Vel omnis, et ea ab, quibusdam adipisci cum culpa provident corporis, dolore ipsa aut quae dolorum, ratione similique porro.</p>
            </div>
        *}
    </div>
</div>