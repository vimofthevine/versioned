<section>
<?php
echo '<h1>', Kohana::lang('page_form_labels.editor_legend'), '</h1>', PHP_EOL;
echo isset($message) ? '<h2>' . $message . '</h2>' . PHP_EOL : '';
echo form::open();

echo empty($errors['title']) ? '' : '<h2>' . $errors['title'] . '</h2>' . PHP_EOL;
echo form::label('title', Kohana::lang('page_form_labels.title')), PHP_EOL;
echo form::input('title', $form['title']), PHP_EOL; 
echo '<br />', PHP_EOL;

echo empty($errors['text']) ? '' : '<h2>' . $errors['text'] . '</h2>' . PHP_EOL;
echo form::label('text', Kohana::lang('page_form_labels.text')), PHP_EOL;
echo form::textarea('text',$form['text']), PHP_EOL;
echo '<br />', PHP_EOL;

echo empty($errors['comment']) ? '' : '<h2>' . $errors['comment'] . '</h2>' . PHP_EOL;
echo form::label('comment', Kohana::lang('page_form_labels.comment')), PHP_EOL;
echo form::input('comment', $form['comment']), PHP_EOL;
echo '<br />', PHP_EOL;

echo form::submit('submit', Kohana::lang('page_form_labels.editor_submit')), PHP_EOL; 
echo form::close(); 
?>
</section>
