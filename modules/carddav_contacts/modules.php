<?php

/**
 * Carddav contact modules
 * @package modules
 * @subpackage carddav_contacts
 */

if (!defined('DEBUG_MODE')) { die(); }

require APP_PATH.'modules/carddav_contacts/hm-carddav.php';

/**
 * @subpackage carddav_contacts/handler
 */
class Hm_Handler_process_edit_carddav_contact extends Hm_Handler_Module {
    public function process() {
        $contacts = $this->get('contact_store');
        $details = get_ini($this->config, 'carddav.ini', true);
        list($success, $form) = $this->process_form(array('contact_source', 'contact_id', 'carddav_email', 'carddav_name', 'edit_contact'));
        if (!$success || !array_key_exists($form['contact_source'], $details)) {
            return;
        }
        //$contact = $contacts->get($form['contact_id']);
    }
}

/**
 * @subpackage carddav_contacts/handler
 */
class Hm_Handler_load_edit_carddav_contact extends Hm_Handler_Module {
    public function process() {
        $details = get_ini($this->config, 'carddav.ini', true);
        if (array_key_exists('contact_source', $this->request->get) &&
            array_key_exists('contact_type', $this->request->get) &&
            $this->request->get['contact_type'] == 'carddav' &&
            array_key_exists($this->request->get['contact_source'], $details) &&
            array_key_exists('contact_id', $this->request->get)) {

            $contacts = $this->get('contact_store');
            $contact = $contacts->get($this->request->get['contact_id']);
            if (is_object($contact)) {
                $current = $contact->export();
                $current['id'] = $this->request->get['contact_id'];
                $this->out('current_carddav_contact', $current);
            }
        }
    }
}

/**
 * @subpackage carddav_contacts/handler
 */
class Hm_Handler_load_carddav_contacts extends Hm_Handler_Module {
    public function process() {

        $contacts = $this->get('contact_store');
        $auths = $this->user_config->get('carddav_contacts_auth_setting', array());
        $details = get_ini($this->config, 'carddav.ini', true);

        foreach ($details as $name => $vals) {
            /* TODO: enable when edit/add is working */
            //$this->append('contact_edit', sprintf('carddav:%s', $name));
            if (!array_key_exists($name, $auths)) {
                continue;
            }
            $pass = '';
            if (array_key_exists('pass', $auths[$name])) {
                $pass = $auths[$name]['pass'];
            }
            $carddav = new Hm_Carddav($name, $vals['server'], $auths[$name]['user'], $pass);
            $contacts->import($carddav->addresses);
            $this->append('contact_sources', 'carddav');
        }
        $this->out('contact_store', $contacts, false);
        $this->out('carddav_sources', $details);
    }
}

/**
 * @subpackage carddav_contacts/handler
 */
class Hm_Handler_load_carddav_settings extends Hm_Handler_Module {
    public function process() {
        $this->out('carddav_settings', get_ini($this->config, 'carddav.ini', true));
        $this->out('carddav_auth', $this->user_config->get('carddav_contacts_auth_setting', array()));
    }
}

/**
 * @subpackage carddav_contacts/handler
 */
class Hm_Handler_process_carddav_auth_settings extends Hm_Handler_Module {
    public function process() {
        if (!array_key_exists('save_settings', $this->request->post)) {
            return;
        }
        $settings = $this->user_config->get('carddav_contacts_auth_setting', array());
        $users = array();
        $passwords = array();
        $results = $settings; 
        if (array_key_exists('carddav_usernames', $this->request->post)) {
            $users = $this->request->post['carddav_usernames'];
        }
        if (array_key_exists('carddav_passwords', $this->request->post)) {
            $passwords = $this->request->post['carddav_passwords'];
        }
        foreach ($settings as $name => $vals) {
            if (array_key_exists($name, $users)) {
                $results[$name]['user'] = $users[$name];
            }
            if (array_key_exists($name, $passwords)) {
                $results[$name]['pass'] = $passwords[$name];
            }
        }
        if (count($results) > 0) {
            $new_settings = $this->get('new_user_settings');
            $new_settings['carddav_contacts_auth_setting'] = $results;
            $this->out('new_user_settings', $new_settings, false);
        }
    }
}

/**
 * @subpackage carddav_contacts/output
 */
class Hm_Output_carddav_auth_settings extends Hm_Output_Module {
    protected function output() {
        $settings = $this->get('carddav_settings', array());
        $auths = $this->get('carddav_auth', array());
        if (count($settings) == 0) {
            return;
        }
        $res = '<tr><td data-target=".carddav_settings" colspan="2" class="settings_subtitle">'.
            '<img alt="" src="'.Hm_Image_Sources::$people.'" width="16" height="16" />'.
            $this->trans('CardDav Addressbooks').'</td></tr>';
        foreach ($settings as $name => $vals) {
            $user = '';
            $pass = false;
            if (array_key_exists($name, $auths)) {
                $user = $auths[$name]['user'];
                if (array_key_exists('pass', $auths[$name]) && $auths[$name]['pass']) {
                    $pass = true;
                }
            }
            $res .= '<tr class="carddav_settings"><td>'.$this->html_safe($name).'</td><td>';
            $res .= '<input autocomplete="username" type="text" value="'.$user.'" name="carddav_usernames['.$this->html_safe($name).']" ';
            $res .= 'placeholder="'.$this->trans('Username').'" /> <input type="password" ';
            if ($pass) {
                $res .= 'disabled="disabled" placeholder="'.$this->trans('Password saved').'" ';
                $res .= 'name="carddav_passwords['.$this->html_safe($name).']" /> <input type="button" ';
                $res .= 'value="'.$this->trans('Unlock').'" class="carddav_password_change" /></td></tr>';
            }
            else {
                $res .= 'autocomplete="new-password" placeholder="'.$this->trans('Password').'" ';
                $res .= 'name="carddav_passwords['.$this->html_safe($name).']" /></td></tr>';
            }
        }
        return $res;
    }
}

/**
 * @subpackage carddav_contacts/output
 */
class Hm_Output_carddav_contacts_form extends Hm_Output_Module {
    protected function output() {

        /* TODO: finish and enable */
        return;
        $email = '';
        $name = '';
        $phone = '';
        $sources = $this->get('carddav_sources', array());
        if (count($sources) == 0) {
            return '';
        }
        $form_class = 'contact_form';
        $button = '<input class="add_contact_submit" type="submit" name="add_carddav_contact" value="'.$this->trans('Add').'" />';
        $title = $this->trans('Add Carddav');
        $current = $this->get('current_carddav_contact', array());
        $current_source = false;
        if (!empty($current)) {
            $current_source = $current['source'];
            if (array_key_exists('email_address', $current)) {
                $email = $current['email_address'];
            }
            if (array_key_exists('display_name', $current)) {
                $name = $current['display_name'];
            }
            if (array_key_exists('phone_number', $current)) {
                $phone = $current['phone_number'];
            }
            $form_class = 'contact_update_form';
            $title = sprintf($this->trans('Update Carddav - %s'), $this->html_safe($current['source']));
            $button = '<input type="hidden" name="contact_id" value="'.$this->html_safe($current['id']).'" />'.
                '<input class="edit_contact_submit" type="submit" name="edit_contact" value="'.$this->trans('Update').'" />';
        }
        if ($current_source) {
            $target = '<input type="hidden" name="contact_source" value="'.$this->html_safe($current_source).'" />';
        }
        else {
            $target = '<label class="screen_reader" for="contact_source">'.$this->trans('Account').'</label><select id="carddav_target" '.
                'name="carddav_target">';
            foreach ($sources as $src => $details) {
                $target .= '<option value="'.$this->html_safe($src).'">'.$this->html_safe($src).'</option>';
            }
            $target .= '</select>';
        }
        return '<div class="add_contact"><form class="add_contact_form" method="POST">'.
            '<div class="server_title">'.$title.
            '<img alt="" class="menu_caret" src="'.Hm_Image_Sources::$chevron.'" width="8" height="8" /></div>'.
            '<div class="'.$form_class.'">'.$target.'<br />'.
            '<input type="hidden" name="hm_page_key" value="'.$this->html_safe(Hm_Request_Key::generate()).'" />'.
            '<label class="screen_reader" for="carddav_email">'.$this->trans('E-mail Address').'</label>'.
            '<input required placeholder="'.$this->trans('E-mail Address').'" id="carddav_email" type="email" name="carddav_email" '.
            'value="'.$this->html_safe($email).'" /> *<br />'.
            '<label class="screen_reader" for="carddav_name">'.$this->trans('Full Name').'</label>'.
            '<input required placeholder="'.$this->trans('Full Name').'" id="carddav_name" type="text" name="carddav_name" '.
            'value="'.$this->html_safe($name).'" /> *<br />'.
            '<label class="screen_reader" for="carddav_phone">'.$this->trans('Telephone Number').'</label>'.
            '<input placeholder="'.$this->trans('Telephone Number').'" id="carddav_phone" type="text" name="carddav_phone" '.
            'value="'.$this->html_safe($phone).'"><br />'.$button.' <input type="button" class="reset_contact" value="'.
            $this->trans('Reset').'" /></div></form></div>';
    }
}
