{if !empty($block_testimonials)}
    <div class="ia-items b-testimonials">
        <div class="row">
            {foreach $block_testimonials as $one_testimonials}
                <div class="col-md-3">
                    <div class="b-testimonial">
                        <div class="b-testimonial__content">
                            {$one_testimonials.body|html_entity_decode:2:"UTF-8"|truncate:$core.config.testimonials_max:"..."}
                            <p class="m-b-0"><a href="testimonials/{$one_testimonials.id}">Read more</a></p>
                        </div>

                        <div class="b-testimonial__author">
                            {if $one_testimonials.avatar}
                                {ia_image file=$one_testimonials.avatar width=60 height=60 class='img-circle'}
                            {else}
                                <img class="img-circle" src="{$img}no-avatar.png" alt="{$one_testimonials.name}" width="60" height="60">
                            {/if}
                            <p class="b-testimonial__author__name">{$one_testimonials.name}</p>
                        </div>
                    </div>
                </div>

                {if $one_testimonials@iteration == 4}
                    {break}
                {/if}
            {/foreach}
        </div>

        <p class="m-t-lg text-center">
            <a class="btn btn-primary-outline m-r" href="{$smarty.const.IA_URL}testimonials/">{lang key='read_more'}</a>
            <a class="btn btn-primary-outline" href="{$smarty.const.IA_URL}testimonials/add/">{lang key='add_yours'}</a>
        </p>
    </div>
{else}
    <div class="alert alert-info">{lang key='no_testimonials_yet'}</div>
{/if}

{ia_add_media files='css: _IA_URL_modules/testimonials/templates/front/css/style'}