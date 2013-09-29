<?php
$content = '
<p class="rex-tx1">'.$I18N->Msg('a587_help_wiki').'</p>
<p class="rex-tx1">'.$I18N->Msg('a587_help_forum').'</p>';

echo rex_register_extension_point('A587_PAGE_HELP', $content);
