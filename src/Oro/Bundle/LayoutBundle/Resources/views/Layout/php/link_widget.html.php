<a <?php echo $view['layout']->block($block, 'block_attributes') ?> href="<?php echo $path ?: $view['router']->generate($route_name, $route_parameters) ?>"><?php echo $view->escape($view['layout']->text($text, $translation_domain)) ?></a>
