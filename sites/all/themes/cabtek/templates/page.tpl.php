<div id="page" class="<?php print $classes; ?>"<?php print $attributes; ?>>
  <?php if ($page['page_first']): ?>
  <div id="page_top">
    <div class="container"> <?php print render($page['page_first']) ?> </div>
  </div>
  <?php endif; ?>
  <!-- ______________________ HEADER _______________________ -->
  
  <header id="header">
    <div class="container"> <a href="<?php print $front_page; ?>" title="<?php print $site_name; ?>" rel="home" id="logo"><span>DIY</span><span id="logo-sub">Custom<br>Kitchens</span></a>
      <?php if ($site_name || $site_slogan): ?>
      <?php if ($site_name): ?>
      <?php if ($title): ?>
      <div id="site-name"> <a href="<?php print $front_page; ?>" title="<?php print $site_name; ?>" rel="home"><?php print $site_name; ?></a> </div>
      <?php else: /* Use h1 when the content title is empty */ ?>
      <h1 id="site-name"> <a href="<?php print $front_page; ?>" title="<?php print $site_name; ?>" rel="home"><?php print $site_name; ?></a> </h1>
      <?php endif; ?>
      <?php endif; ?>
      <?php if ($site_slogan): ?>
      <div id="site-slogan"><?php print $site_slogan; ?></div>
      <?php endif; ?>
      <?php endif; ?>
      <?php if ($page['header']): ?>
      <div id="header-region"> <?php print render($page['header']); ?> </div>
      <?php endif; ?>
    </div>
  </header>
  <!-- /header -->
  
  <?php if ($main_menu): ?>
  <nav id="navigation" class="menu">
    <div class="container">
    	<?php print theme('links', array('links' => $main_menu, 'attributes' => array('id' => 'primary', 'class' => array('main-menu')))); ?>
    </div>
  </nav>
  <!-- /navigation -->
  <?php endif; ?>
  
  <!-- ______________________ MAIN _______________________ -->
  
  <div id="main">
    <div class="container">
      <section id="content">
        <?php if ($breadcrumb || $title|| $messages || $tabs || $action_links): ?>
        <!-- <div id="content-header"> --> 
        
        <?php print $breadcrumb; ?>
        <?php if ($page['highlighted']): ?>
        <div id="highlighted"><?php print render($page['highlighted']) ?></div>
        <?php endif; ?>
        <?php print render($title_prefix); ?>
        <?php if ($title): ?>
        <h1 class="title"><?php print $title; ?></h1>
        <?php endif; ?>
        <?php print render($title_suffix); ?> <?php print $messages; ?> <?php print render($page['help']); ?>
        <?php if (render($tabs)): ?>
        <div class="tabs"><?php print render($tabs); ?></div>
        <?php endif; ?>
        <?php if ($action_links): ?>
        <ul class="action-links">
          <?php print render($action_links); ?>
        </ul>
        <?php endif; ?>
        
        <!-- </div> /#content-header -->
        <?php endif; ?>
        <div id="content-area"> <?php print render($page['content']) ?> </div>
        <?php print $feed_icons; ?> </section>
      <!-- /content-inner /content -->
      
      <?php if ($page['sidebar_first']): ?>
      <aside id="sidebar-first"> <?php print render($page['sidebar_first']); ?> </aside>
      <?php endif; ?>
      <!-- /sidebar-first -->
      
      <?php if ($page['sidebar_second']): ?>
      <aside id="sidebar-second"> <?php print render($page['sidebar_second']); ?> </aside>
      <?php endif; ?>
      <!-- /sidebar-second --> 
    </div>
  </div>
  <!-- /main --> 
  
  <!-- ______________________ FOOTER _______________________ -->
  
  <?php if ($page['footer']): ?>
  <footer id="footer">
  	<?php if ($page['footer_super']): ?>
    	<div class="footer-super"><?php print render($page['footer_super']); ?></div>
    <?php endif; ?>
    <div class="container">
    	<?php if ($secondary_menu): ?>
      	<nav class="footer-menu">
        	<div class="container">
          	<?php print theme('links', array('links' => $secondary_menu, 'attributes' => array('id' => 'secondary', 'class' => array('sub-menu')))); ?>
          </div>
        </nav>
      <?php endif; ?>
      <!-- /secondary menu -->
      
      <?php print render($page['footer']); ?>
    </div>
  </footer>
  <!-- /footer -->
  <?php endif; ?>
</div>
<!-- /page --> 
