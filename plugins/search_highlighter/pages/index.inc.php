<?php
if(isset($_POST['sendit']))
{
    a685_search_highlighter_saveSettings($_POST['a685_rexsearch_search_highlighter']);

    header('Location: http://'.$_SERVER['HTTP_HOST'].substr($_SERVER["PHP_SELF"],0,-9).'index.php?page=rexsearch&subpage=search_highlighter&saved=1');
}

$parent = 'rexsearch';
$mypage = 'search_highlighter';

$basedir = dirname(__FILE__);
$page = rex_request('page', 'string');
$subpage = rex_request('subpage', 'string');
$func = rex_request('func', 'string');



include $REX['INCLUDE_PATH'].'/layout/top.php';



rex_title("RexSearch", $REX['ADDON'][$page]['SUBPAGES']);



if (isset($_GET["saved"])) echo rex_info("Einstellungen gespeichert");

?>

<div class="rex-addon-output" id="a685-form">
    <div class="rex-area">

        <div class="rex-form">
            <form method="post" action="index.php?page=rexsearch&amp;subpage=search_highlighter" id="a685_search_highlighter_form">

                <?php


                foreach(array('b', 'span', 'strong', 'em', 'p', 'div') as $option)
                {
                    $options[] = array(
                            'value' => $option,
                            'selected' => !empty($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['tag']) AND ($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['tag'] == $option),
                            'name' => $option
                    );
                }

                foreach(array('stil1', 'stil2', 'stilEigen') as $option)
                {
                    $optionsstil[] = array(
                            'value' => $option,
                            'selected' => !empty($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stil']) AND ($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stil'] == $option),
                            'name' => $option
                    );
                }


                echo a587_getSettingsFormSection('autocompleter_settings', 'Einstellungen',
                array(
                array(
                        'type' => 'select',
                        'id' => 'a685_rexsearch_search_highlighter_tag',
                        'name' => 'a685_rexsearch_search_highlighter[tag]',
                        'label' => 'Tag um die Suchbegriffe',
                        'options' => $options
                ),
                array(
                        'type' => 'string',
                        'id' => 'a685_rexsearch_search_highlighter_class',
                        'name' => 'a685_rexsearch_search_highlighter[class]',
                        'label' => 'Class',
                        'value' => isset($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['class']) ? $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['class'] : ''
                ),
                array(
                        'type' => 'string',
                        'id' => 'a685_rexsearch_search_highlighter_inlineCSS',
                        'name' => 'a685_rexsearch_search_highlighter[inlineCSS]',
                        'label' => 'inline CSS',
                        'value' => isset($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['inlineCSS']) ? $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['inlineCSS'] : ''
                ),
                array(
                        'type' => 'checkbox',
                        'id' => 'a685_rexsearch_search_highlighter_stilEinbinden',
                        'name' => 'a685_rexsearch_search_highlighter[stilEinbinden]',
                        'label' => 'Stil CSS einbinden<br /> (Klasse class_search_685)',
                        'value' => '1',
                        'checked' => !empty($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stilEinbinden']) && $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stilEinbinden'] == 1
                ),
                array(
                        'type' => 'select',
                        'id' => 'a685_rexsearch_search_highlighter_stil',
                        'name' => 'a685_rexsearch_search_highlighter[stil]',
                        'label' => 'Stil (CSS)',
                        'options' => $optionsstil
                ),

                array(
                        'type' => 'hidden',
                        'id' => 'a685_rexsearch_search_highlighter_stil1',
                        'name' => 'a685_rexsearch_search_highlighter[stil1]',
                        'value' => isset($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stil1']) ? $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stil1'] : ''
                ),
                array(
                        'type' => 'hidden',
                        'id' => 'a685_rexsearch_search_highlighter_stil2',
                        'name' => 'a685_rexsearch_search_highlighter[stil2]',
                        'value' => isset($REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stil2']) ? $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stil2'] : ''
                )
                )
                );

                ?>

                <div class="rex-form-row">
                    <p class="rex-form-col-a rex-form-text">
                        <label for="a685_rexsearch_search_highlighter_stilEigen">Eigener Stil</label>
                        <textarea class="rex-form-text" id="a685_rexsearch_search_highlighter_stilEigen" name="a685_rexsearch_search_highlighter[stilEigen]" cols="80" rows="20"><?php echo $REX['ADDON']['rexsearch_plugins'][$parent][$mypage]['settings']['stilEigen']; ?></textarea>
                    </p>
                </div>


                <div class="rex-form-row">
                    <p class="rex-form-col-a rex-form-submit">
                        <input type="submit" value="<?php echo $I18N->Msg('a587_settings_submitbutton'); ?>" name="sendit" class="rex-form-submit"/>
                    </p>
                </div>


            </form>

            <div class="rex-form-row">
                <p class="rex-form-col-a rex-form-text" style="margin: 5px;">
                    F&uuml;r die Ausgabe wird eine modifizierte <a href="http://wiki.redaxo.de/index.php?n=R4.RexSearch#example_result2" target="_blank">RexSearch Ausgabemaske</a> ben&ouml;tigt.
                    <br />
                    Der Suchterm muss an den aufgerufenen Artikel &uuml;bergeben werden. Dies geschieht mit dem Querystring &quot;&amp;search_highlighter=&quot;
                </p>

                <div style="overflow: auto;">
                    <?php
                   rex_highlight_string('<h4><a href="\'. ($url = htmlspecialchars($article->getUrl()) . \'&search_highlighter=\' . urlencode($_REQUEST[\'rexsearch\'])) .\'">\'.$article->getName().\'</a></h4>');
                    ?>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
include $REX['INCLUDE_PATH'].'/layout/bottom.php';
?>
