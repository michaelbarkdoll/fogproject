<?php
/**
 * Host management page
 *
 * PHP version 5
 *
 * The host represented to the GUI
 *
 * @category HostManagementPage
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Host management page
 *
 * The host represented to the GUI
 *
 * @category HostManagementPage
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class HostManagementPage extends FOGPage
{
    /**
     * The node that uses this class.
     *
     * @var string
     */
    public $node = 'host';
    /**
     * Initializes the host page
     *
     * @param string $name the name to construct with
     *
     * @return void
     */
    public function __construct($name = '')
    {
        $this->name = 'Host Management';
        parent::__construct($this->name);
        if (self::$pendingHosts > 0) {
            $this->menu['pending'] = self::$foglang['PendingHosts'];
        }
        global $id;
        if ($id) {
            $linkstr = "$this->linkformat#host-%s";
            $this->subMenu = array(
                sprintf(
                    $linkstr,
                    'general'
                ) => self::$foglang['General'],
            );
            if (!$this->obj->get('pending')) {
                $this->subMenu = self::fastmerge(
                    $this->subMenu,
                    array(
                        sprintf(
                            $linkstr,
                            'tasks'
                        ) => self::$foglang['BasicTasks'],
                    )
                );
            }
            $this->subMenu = self::fastmerge(
                $this->subMenu,
                array(
                    sprintf(
                        $linkstr,
                        'active-directory'
                    ) => self::$foglang['AD'],
                    sprintf(
                        $linkstr,
                        'printers'
                    ) => self::$foglang['Printers'],
                    sprintf(
                        $linkstr,
                        'snapins'
                    ) => self::$foglang['Snapins'],
                    sprintf(
                        $linkstr,
                        'service'
                    ) => sprintf(
                        '%s %s',
                        self::$foglang['Service'],
                        self::$foglang['Settings']
                    ),
                    sprintf(
                        $linkstr,
                        'powermanagement'
                    ) => self::$foglang['PowerManagement'],
                    sprintf(
                        $linkstr,
                        'hardware-inventory'
                    ) => self::$foglang['Inventory'],
                    sprintf(
                        $linkstr,
                        'login-history'
                    ) => self::$foglang['LoginHistory'],
                    sprintf(
                        $linkstr,
                        'image-history'
                    ) => self::$foglang['ImageHistory'],
                    sprintf(
                        $linkstr,
                        'snapin-history'
                    ) => self::$foglang['SnapinHistory'],
                    $this->membership => self::$foglang['Membership'],
                    $this->delformat => self::$foglang['Delete'],
                )
            );
            $this->notes = array(
                self::$foglang['Host'] => $this->obj->get('name'),
                self::$foglang['MAC'] => $this->obj->get('mac'),
                self::$foglang['Image'] => $this->obj->getImageName(),
                self::$foglang['LastDeployed'] => $this->obj->get('deployed'),
            );
            $primaryGroup = @min($this->obj->get('groups'));
            $Group = new Group($primaryGroup);
            if ($Group->isValid()) {
                $this->notes[self::$foglang['PrimaryGroup']] = $Group->get('name');
                unset($Group);
            }
        }
        if (!($this->obj instanceof Host && $this->obj->isValid())) {
            $this->exitNorm = filter_input(INPUT_POST, 'bootTypeExit');
            $this->exitEfi = filter_input(INPUT_POST, 'efiBootTypeExit');
        } else {
            $this->exitNorm = $this->obj->get('biosexit');
            $this->exitEfi = $this->obj->get('efiexit');
        }
        $this->exitNorm = Service::buildExitSelector(
            'bootTypeExit',
            $this->exitNorm,
            true,
            'bootTypeExit'
        );
        $this->exitEfi = Service::buildExitSelector(
            'efiBootTypeExit',
            $this->exitEfi,
            true,
            'efiBootTypeExit'
        );
        self::$HookManager->processEvent(
            'SUB_MENULINK_DATA',
            array(
                'menu' => &$this->menu,
                'submenu' => &$this->subMenu,
                'notes' => &$this->notes,
                'biosexit' => &$this->exitNorm,
                'efiexit' => &$this->exitEfi,
                'object' => &$this->obj,
                'linkformat' => &$this->linkformat,
                'delformat' => &$this->delformat,
                'membership' => &$this->membership
            )
        );
        array_push(
            $this->headerData,
            _('Host'),
            _('Primary MAC')
        );

        self::$fogpingactive ? array_push($this->headerData, 'Ping Status') : null;
        array_push(
            $this->headerData,
            _('Imaged'),
            _('Assigned Image'),
            'Description'
        );
        $this->templates = array();

        array_push(
            $this->templates,
            '<a href="?node=host&sub=edit&id=${id}" '
            . 'title="'
            . _('Edit')
            . ': ${host_name}" id="host-${host_name}" '
            . 'data-toggle="tooltip" data-placement="right">'
            . '${host_name}'
            . '</a>',
            '<small>${host_mac}</small>'
        );
        if (self::$fogpingactive) {
            array_push(
                $this->templates,
                '${pingstatus}'
            );
        }
        array_push(
            $this->templates,
            '<small>${deployed}</small>',
            '<small><a href="?node=image&sub=edit&id=${image_id}">'
            . '${image_name}</a></small>',
            '${host_desc}'
        );
        unset($up, $down, $mc);
        $this->attributes = array(
            array(
                'width' => 2,
                'id' => 'host-${host_name}',
                'class' => 'filter-false',
                'data-placement' => 'right'
            ),
            array(
                'class' => '',
                'width' => 16
            ),
        );
        if (self::$fogpingactive) {
            array_push(
                $this->attributes,
                array(
                    'width' => 16,
                    'class' => 'filter-false'
                )
            );
        }
        array_push(
            $this->attributes,
            array('width' => 145),
            array(
                'width' => 60,
                'class' => 'filter-false'
            ),
            array(
                'width' => 20
            )
        );
        /**
         * Lambda function to return data either by list or search.
         *
         * @param object $Host the object to use.
         *
         * @return void
         */
        self::$returnData = function (&$Host) {
            $this->data[] = array(
                'id' => $Host->id,
                'deployed' => self::formatTime(
                    $Host->deployed,
                    'Y-m-d H:i:s'
                ),
                'host_name' => $Host->name,
                'host_mac' => $Host->primac,
                'host_desc' => $Host->description,
                'image_id' => $Host->imageID,
                'image_name' => $Host->imagename,
                'pingstatus' => $Host->pingstatus,
            );
            unset($Host);
        };
    }
    /**
     * Lists the pending hosts
     *
     * @return false
     */
    public function pending()
    {
        $this->title = _('Pending Host List');
        $this->data = array();
        Route::listem(
            'host',
            'name',
            false,
            array('pending' => 1)
        );
        $Hosts = json_decode(
            Route::getData()
        );
        $Hosts = $Hosts->hosts;
        array_map(self::$returnData, $Hosts);
        self::$HookManager->processEvent(
            'HOST_DATA',
            array(
                'data' => &$this->data,
                'templates' => &$this->templates,
                'attributes' => &$this->attributes
            )
        );
        self::$HookManager->processEvent(
            'HOST_HEADER_DATA',
            array(
                'headerData' => &$this->headerData
            )
        );
        if (count($this->data) > 0) {
            printf(
                '<form class="form-horizontal" method="post" action="%s">',
                $this->formAction
            );
        }
        $this->render();
        if (count($this->data) > 0) {
            echo '<button name="approvependhost" type="submit" id='
                . '"approvependhost" class='
                . '"btn btn-info">'
                . _('Approve selected hosts')
                . '</button>'
                . '<button name="delpendhost" type="submit" id='
                . '"delpendhost" class='
                . '"btn btn-danger">'
                . _('Delete selected hosts')
                . '</button>';
            echo '</form>';
        }
    }
    /**
     * Pending host form submitting
     *
     * @return void
     */
    public function pendingPost()
    {
        $host = filter_input_array(
            INPUT_POST,
            array(
                'host' => array(
                    'flags' => FILTER_REQUIRE_ARRAY
                )
            )
        );
        $host = $host['host'];
        if (isset($_POST['approvependhost'])) {
            self::getClass('HostManager')->update(
                array(
                    'id' => $host
                ),
                '',
                array('pending' => 0)
            );
            $title = _('Approve Success');
            $msg = _('Selected hosts approved successfully');
        }
        if (isset($_POST['delpendhost'])) {
            self::getClass('HostManager')->destroy(
                array(
                    'id' => $host
                )
            );
            $title = _('Deleted Success');
            $msg = _('Selected hosts deleted successfully');
        }
        $msg = json_encode(
            array(
                'msg' => $msg,
                'title' => $title
            )
        );
        echo $msg;
        exit;
    }
    /**
     * Creates a new host entry manually.
     *
     * @return void
     */
    public function add()
    {
        $this->title = _('Create New Host');
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
        // Check all the post fields if they've already been set.
        $host = filter_input(INPUT_POST, 'host');
        $mac = filter_input(INPUT_POST, 'mac');
        $description = filter_input(INPUT_POST, 'description');
        $key = filter_input(INPUT_POST, 'key');
        $image = filter_input(INPUT_POST, 'image');
        $kern = filter_input(INPUT_POST, 'kern');
        $args = filter_input(INPUT_POST, 'args');
        $init = filter_input(INPUT_POST, 'init');
        $dev = filter_input(INPUT_POST, 'dev');
        $domain = filter_input(INPUT_POST, 'domain');
        $domainname = filter_input(INPUT_POST, 'domainname');
        $ou = filter_input(INPUT_POST, 'ou');
        $domainuser = filter_input(INPUT_POST, 'domainuser');
        $domainpassword = filter_input(INPUT_POST, 'domainpassword');
        $enforcesel = isset($_POST['enforcesel']);

        // The fields to display
        $fields = array(
            '<label class="col-sm-2 control-label" for="host">'
            . _('Host Name')
            . '</label>' => '<input type="text" name="host" '
            . 'value="'
            . $host
            . '" maxlength="15" '
            . 'class="hostname-input form-control" '
            . 'id="host" required/>',
            '<label class="col-sm-2 control-label" for="mac">'
            . _('Primary MAC')
            . '</label>' => '<input type="text" name="mac" class="macaddr form-control" '
            . 'id="mac" value="'
            . $mac
            . '" maxlength="17" exactlength="12" required/>',
            '<label class="col-sm-2 control-label" for="description">'
            . _('Host Description')
            . '</label>' => '<textarea class="form-control" style="resize:vertical;'
            . 'min-height:50px;" '
            . 'id="description" name="description">'
            . $description
            . '</textarea>',
            '<label class="col-sm-2 control-label" for="productKey">'
            . _('Host Product Key')
            . '</label>' => '<input id="productKey" type="text" '
            . 'name="key" value="'
            . $key
            . '" class="form-control" maxlength="29" exactlength="25"/>',
            '<label class="col-sm-2 control-label" for="image">'
            . _('Host Image')
            . '</label>' => self::getClass('ImageManager')->buildSelectBox(
                $image,
                '',
                'id'
            ),
            '<label class="col-sm-2 control-label" for="kern">'
            . _('Host Kernel')
            . '</label>' => '<input type="text" name="kern" '
            . 'value="'
            . $kern
            . '" class="form-control" id="kern"/>',
            '<label class="col-sm-2 control-label" for="args">'
            . _('Host Kernel Arguments')
            . '</label>' => '<input type="text" name="args" id="args" value="'
            . $args
            . '" class="form-control"/>',
            '<label class="col-sm-2 control-label" for="init">'
            . _('Host Init')
            . '</label>' => '<input type="text" name="init" value="'
            . $init
            . '" id="init" class="form-control"/>',
            '<label class="col-sm-2 control-label" for="dev">'
            . _('Host Primary Disk')
            . '</label>' => '<input type="text" name="dev" value="'
            . $dev
            . '" id="dev" class="form-control"/>',
            '<label class="col-sm-2 control-label" for="bootTypeExit">'
            . _('Host Bios Exit Type')
            . '</label>' => $this->exitNorm,
            '<label class="col-sm-2 control-label" for="efiBootTypeExit">'
            . _('Host EFI Exit Type')
            . '</label>' => $this->exitEfi,
        );
        self::$HookManager
            ->processEvent(
                'HOST_ADD_FIELDS',
                array(
                    'fields' => &$fields,
                    'Host' => self::getClass('Host')
                )
            );
        $rendered = self::formFields($fields);
        unset($fields);
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<h3 class="box-title">';
        echo _('Create New Host');
        echo '</h3>';
        echo '</div>';
        echo '<form id="host-create-form" class="form-horizontal" method="post" action="'
            . $this->formAction
            . '" novalidate>';
        echo '<div class="box-body">';
        if (!isset($_POST['enforcesel'])) {
            $_POST['enforcesel'] = self::getSetting('FOG_ENFORCE_HOST_CHANGES');
        }
        echo '<!-- Host General -->';
        echo $rendered;
        echo '</br>';
        echo '<h5><b><center>' . _('Active Directory') . '</center></b></h5>';
        $this->adFieldsToDisplay(
            $domain,
            $domainname,
            $ou,
            $domainuser,
            $domainpassword,
            $enforcesel,
            false
        );
        echo '  </div>';
        echo '  <div class="box-footer">';
        echo '      <button class="btn btn-primary" id="send">' . _('Create') . '</button>';
        echo '  </div>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Handles the forum submission process.
     *
     * @return void
     */
    public function addPost()
    {
        header('Content-type: application/json');
        self::$HookManager->processEvent('HOST_ADD_POST');
        $name = trim(
            filter_input(INPUT_POST, 'host')
        );
        $mac = trim(
            filter_input(INPUT_POST, 'mac')
        );
        $desc = trim(
            filter_input(INPUT_POST, 'description')
        );
        $password = trim(
            filter_input(INPUT_POST, 'domainpassword')
        );
        $useAD = (int)isset($_POST['domain']);
        $domain = trim(
            filter_input(INPUT_POST, 'domainname')
        );
        $ou = trim(
            filter_input(INPUT_POST, 'ou')
        );
        $user = trim(
            filter_input(INPUT_POST, 'domainuser')
        );
        $pass = $password;
        $key = trim(
            filter_input(INPUT_POST, 'key')
        );
        $productKey = preg_replace(
            '/([\w+]{5})/',
            '$1-',
            str_replace(
                '-',
                '',
                strtoupper($key)
            )
        );
        $productKey = substr($productKey, 0, 29);
        $enforce = (int)isset($_POST['enforcesel']);
        $image = (int)filter_input(INPUT_POST, 'image');
        $kernel = trim(
            filter_input(INPUT_POST, 'kern')
        );
        $kernelArgs = trim(
            filter_input(INPUT_POST, 'args')
        );
        $kernelDevice = trim(
            filter_input(INPUT_POST, 'dev')
        );
        $init = trim(
            filter_input(INPUT_POST, 'init')
        );
        $bootTypeExit = trim(
            filter_input(INPUT_POST, 'bootTypeExit')
        );
        $efiBootTypeExit = trim(
            filter_input(INPUT_POST, 'efiBootTypeExit')
        );
        $serverFault = false;
        try {
            if (!$name) {
                throw new Exception(
                    _('A host name is required!')
                );
            }
            if (!$mac) {
                throw new Exception(
                    _('A mac address is required!')
                );
            }
            if (self::getClass('HostManager')->exists($name)) {
                throw new Exception(
                    _('A host already exists with this name!')
                );
            }
            $MAC = new MACAddress($mac);
            if (!$MAC->isValid()) {
                throw new Exception(_('MAC Format is invalid'));
            }
            self::getClass('HostManager')->getHostByMacAddresses($MAC);
            if (self::$Host->isValid()) {
                throw new Exception(
                    sprintf(
                        '%s: %s',
                        _('A host with this mac already exists with name'),
                        self::$Host->get('name')
                    )
                );
            }
            $ModuleIDs = self::getSubObjectIDs(
                'Module',
                array('isDefault' => 1)
            );
            self::$Host
                ->set('name', $name)
                ->set('description', $desc)
                ->set('imageID', $image)
                ->set('kernel', $kernel)
                ->set('kernelArgs', $kernelArgs)
                ->set('kernelDevice', $kernelDevice)
                ->set('init', $init)
                ->set('biosexit', $bootTypeExit)
                ->set('efiexit', $efiBootTypeExit)
                ->set('productKey', $productKey)
                ->addModule($ModuleIDs)
                ->addPriMAC($MAC)
                ->setAD(
                    $useAD,
                    $domain,
                    $ou,
                    $user,
                    $pass,
                    true,
                    true,
                    $productKey,
                    $enforce
                );
            if (!self::$Host->save()) {
                $serverFault = true;
                throw new Exception(_('Add host failed!'));
            }
            $hook = 'HOST_ADD_SUCCESS';
            $msg = json_encode(
                array(
                    'msg' => _('Host added!'),
                    'title' => _('Host Create Success')
                )
            );
        } catch (Exception $e) {
            http_response_code(($serverFault) ? 500 : 400);
            $hook = 'HOST_ADD_FAIL';
            $msg = json_encode(
                array(
                    'error' => $e->getMessage(),
                    'title' => _('Host Create Fail')
                )
            );
        }
        self::$HookManager
            ->processEvent(
                $hook,
                array('Host' => &$Host)
            );
        echo $msg;
        exit;
    }
    /**
     * Generates the powermanagement display items.
     *
     * @return void
     */
    public function hostPMDisplay()
    {
        echo '<!-- Power Management Items -->';
        echo '<div class="tab-pane fade" id="host-powermanagement">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">';
        echo _('Power Management');
        echo '</h4>';
        echo '</div>';
        echo '<div class="panel-body">';
        $this->newPMDisplay();
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
        // PowerManagement
        $this->headerData = array(
            '<div class="checkbox">'
            . '<label for="rempowerselectors">'
            . '<input type="checkbox" id="rempowerselectors"/>'
            . '</label>'
            . '</div>',
            _('Cron Schedule'),
            _('Action'),
        );
        $this->templates = array(
            '<input type="checkbox" name="rempowermanagements[]" '
            . 'class="rempoweritems" value="${id}" id="rmpm-${id}"/>'
            . '<label for="rmpm-${id}"></label>',
            '<div class="cronOptions input-group">'
            . FOGCron::buildSpecialCron()
            . '</div>'
            . '<div class="col-xs-12">'
            . '<div class="cronInputs">'
            . '<div class="col-xs-2">'
            . '<input type="hidden" name="pmid[]" value="${id}"/>'
            . '<div class="input-group">'
            . '<input type="text" name="scheduleCronMin[]" '
            . 'class="scheduleCronMin form-control cronInput" value="${min}" '
            . 'id="scheduleCronMin"/>'
            . '</div>'
            . '</div>'
            . '<div class="col-xs-2">'
            . '<div class="input-group">'
            . '<input type="text" name="scheduleCronHour[]" '
            . 'class="scheduleCronHour form-control cronInput" value="${hour}" '
            . 'id="scheduleCronHour"/>'
            . '</div>'
            . '</div>'
            . '<div class="col-xs-2">'
            . '<div class="input-group">'
            . '<input type="text" name="scheduleCronDOM[]" '
            . 'class="scheduleCronDOM form-control cronInput" value="${dom}" '
            . 'id="scheduleCronDOM"/>'
            . '</div>'
            . '</div>'
            . '<div class="col-xs-2">'
            . '<div class="input-group">'
            . '<input type="text" name="scheduleCronMonth[]" '
            . 'class="scheduleCronMonth form-control cronInput" value="${month}" '
            . 'id="scheduleCronMonth"/>'
            . '</div>'
            . '</div>'
            . '<div class="col-xs-2">'
            . '<div class="input-group">'
            . '<input type="text" name="scheduleCronDOW[]" '
            . 'class="scheduleCronDOW form-control cronInput" value="${dow}" '
            . 'id="scheduleCronDOW"/>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '</div>',
            '${action}',
        );
        $this->attributes = array(
            array(
                'width' => 16,
                'class' => 'filter-false'
            ),
            array(
                'class' => 'filter-false'
            ),
            array(
                'class' => 'filter-false'
            )
        );
        Route::listem('powermanagement');
        $PowerManagements = json_decode(
            Route::getData()
        );
        $PowerManagements = $PowerManagements->powermanagements;
        foreach ((array)$PowerManagements as &$PowerManagement) {
            $mine = in_array(
                $PowerManagement->id,
                $this->obj->get('powermanagementtasks')
            );
            if (!$mine) {
                continue;
            }
            if ($PowerManagement->onDemand) {
                continue;
            }
            $this->data[] = array(
                'id' => $PowerManagement->id,
                'min' => $PowerManagement->min,
                'hour' => $PowerManagement->hour,
                'dom' => $PowerManagement->dom,
                'month' => $PowerManagement->month,
                'dow' => $PowerManagement->dow,
                'action' => self::getClass('PowerManagementManager')
                ->getActionSelect(
                    $PowerManagement->action,
                    true
                )
            );
            unset($PowerManagement);
        }
        // Current data.
        if (count($this->data) > 0) {
            echo '<div class="panel panel-info">';
            echo '<div class="panel-heading text-center">';
            echo '<h4 class="title">';
            echo _('Current Power Management settings');
            echo '</h4>';
            echo '</div>';
            echo '<div class="body">';
            echo '<form class="deploy-container form-horizontal" '
                . 'method="post" action="'
                . $this->formAction
                . '&tab=host-powermanagement">';
            $this->render(12);
            echo '<div class="form-group">';
            echo '<label class="col-xs-4 control-label" for="pmupdate">';
            echo _('Update PM Values');
            echo '</label>';
            echo '<div class="col-xs-8">';
            echo '<button type="submit" name="pmupdate" class='
                . '"btn btn-info btn-block" id="pmupdate">';
            echo _('Update');
            echo '</button>';
            echo '</div>';
            echo '</div>';
            echo '<div class="form-group">';
            echo '<label class="col-xs-4 control-label" for="pmdelete">';
            echo _('Delete selected');
            echo '</label>';
            echo '<div class="col-xs-8">';
            echo '<button type="submit" name="pmdelete" class='
                . '"btn btn-danger btn-block" id="pmdelete">';
            echo _('Remove');
            echo '</button>';
            echo '</div>';
            echo '</div>';
            echo '</form>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Displays the host general tab.
     *
     * @return void
     */
    public function hostGeneral()
    {
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->attributes,
            $this->templates
        );
        $image = filter_input(INPUT_POST, 'image') ?: $this->obj->get('imageID');
        $imageSelect = self::getClass('ImageManager')
            ->buildSelectBox($image);
        // Either use the passed in or get the objects info.
        $name = (
            filter_input(INPUT_POST, 'name') ?: $this->obj->get('name')
        );
        $mac = (
            filter_input(INPUT_POST, 'mac') ?: $this->obj->get('mac')
        );
        $desc = (
            filter_input(INPUT_POST, 'description') ?: $this->obj->get('description')
        );
        $productKey = (
            filter_input(INPUT_POST, 'key') ?: $this->obj->get('productKey')
        );
        $productKeytest = self::aesdecrypt($productKey);
        if ($test_base64 = base64_decode($productKeytest)) {
            if (mb_detect_encoding($test_base64, 'utf-8', true)) {
                $productKey = $test_base64;
            }
        } elseif (mb_detect_encoding($productKeytest, 'utf-8', true)) {
            $productKey = $productKeytest;
        }
        $kern = (
            filter_input(INPUT_POST, 'kern') ?: $this->obj->get('kernel')
        );
        $args = (
            filter_input(INPUT_POST, 'args') ?: $this->obj->get('kernelArgs')
        );
        $init = (
            filter_input(INPUT_POST, 'init') ?: $this->obj->get('init')
        );
        $dev = (
            filter_input(INPUT_POST, 'dev') ?: $this->obj->get('kernelDevice')
        );
        $fields = array(
            '<label for="name" class="col-sm-2 control-label">'
            . _('Host Name')
            . '</label>' => '<input id="name" class="form-control" placeholder="'
            . _('Host Name')
            . '" type="text" value="'
            . $name
            . '" maxlength="15" name="host" required>',
            '<label for="mac" class="col-sm-2 control-label">'
            . _('Primary MAC')
            . '</label>' => '<input name="mac" id="mac" class="form-control" value="'
            . $mac
            . '" maxlength="17" exactlength="12" required>',
            '<label for="description" class="col-sm-2 control-label">'
            . _('Host description')
            . '</label>' => '<textarea style="resize:vertical;'
            . 'min-height:50px;" id="description" name="description" class="form-control">'
            . $desc
            . '</textarea>',
            '<label for="productKey" class="col-sm-2 control-label">'
            . _('Host Product Key')
            . '</label>' => '<input id="productKey" name="key" class="form-control" '
            . 'value="'
            . $productKey
            . '" maxlength="29" exactlength="25">',
            '<label class="col-sm-2 control-label" for="image">'
            . _('Host Image')
            . '</label>' => $imageSelect,
            '<label for="kern" class="col-sm-2 control-label">'
            . _('Host Kernel')
            . '</label>' => '<input id="kern" name="kern" class="form-control" '
            . 'placeholder="" type="text" value="'
            . $kern
            . '">',
            '<label for="args" class="col-sm-2 control-label">'
            . _('Host Kernel Arguments')
            . '</label>' => '<input id="args" name="args" class="form-control" '
            . 'placeholder="" type="text" value="'
            . $args
            . '">',
            '<label for="init" class="col-sm-2 control-label">'
            . _('Host Init')
            . '</label>' => '<input id="init" name="init" class="form-control" '
            . 'placeholder="" type="text" value="'
            . $init
            . '">',
            '<label for="dev" class="col-sm-2 control-label">'
            . _('Host Primary Disk')
            . '</label>' => '<input id="dev" name="dev" class="form-control" '
            . 'placeholder="" type="text" value="'
            . $dev
            . '">',
            '<label for="bootTypeExit" class="col-sm-2 control-label">'
            . _('Host Bios Exit Type')
            . '</label>' => $this->exitNorm,
            '<label for="efiBootTypeExit" class="col-sm-2 control-label">'
            . _('Host EFI Exit Type')
            . '</label>' => $this->exitEfi
        );
        self::$HookManager->processEvent(
            'HOST_EDIT_FIELDS',
            array(
                'fields' => &$fields,
                'obj' => &$this->obj
            )
        );
        $rendered = self::formFields($fields);
        echo '<div class="box box-solid">';
        echo '<form id="host-general-form" class="form-horizontal" method="post" action="'
            . self::makeTabUpdateURL('host-general', $this->obj->get('id'))
            . '" novalidate>';
        echo '  <div class="box-body">';
        echo $rendered;
        echo '  </div>';
        echo '  <div class="box-footer">';
        echo '      <button class="btn btn-primary" id="general-send">' . _('Update') . '</button>';
        echo '      <button class="btn btn-danger pull-right" id="general-delete">' . _('Delete') . '</button>';
        echo '  </div>';
        echo '</form>';
        echo '</div>';
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->attributes,
            $this->templates
        );
    }
    /**
     * Host general post update.
     *
     * @return void
     */
    public function hostGeneralPost()
    {
        $name = trim(
            filter_input(INPUT_POST, 'host')
        );
        $mac = trim(
            filter_input(INPUT_POST, 'mac')
        );
        $desc = trim(
            filter_input(INPUT_POST, 'description')
        );
        $imageID = trim(
            filter_input(INPUT_POST, 'image')
        );
        $key = strtoupper(
            trim(
                filter_input(INPUT_POST, 'key')
            )
        );
        $productKey = preg_replace(
            '/([\w+]{5})/',
            '$1-',
            str_replace(
                '-',
                '',
                $key
            )
        );
        $productKey = substr($productKey, 0, 29);
        $kern = trim(
            filter_input(INPUT_POST, 'kern')
        );
        $args = trim(
            filter_input(INPUT_POST, 'args')
        );
        $dev = trim(
            filter_input(INPUT_POST, 'dev')
        );
        $init = trim(
            filter_input(INPUT_POST, 'init')
        );
        $bte = trim(
            filter_input(INPUT_POST, 'bootTypeExit')
        );
        $ebte = trim(
            filter_input(INPUT_POST, 'efiBootTypeExit')
        );
        if (empty($name)) {
            throw new Exception(_('Please enter a hostname'));
        }
        if ($name != $this->obj->get('name')
        ) {
            if (!$this->obj->isHostnameSafe($name)) {
                throw new Exception(_('Please enter a valid hostname'));
            }
            if ($this->obj->getManager()->exists($name)) {
                throw new Exception(_('Please use another hostname'));
            }
        }
        if (empty($mac)) {
            throw new Exception(_('Please enter a mac address'));
        }
        $mac = self::parseMacList($mac);
        if (count($mac) < 1) {
            throw new Exception(_('Please enter a valid mac address'));
        }
        $mac = array_shift($mac);
        if (!$mac->isValid()) {
            throw new Exception(_('Please enter a valid mac address'));
        }
        $Task = $this->obj->get('task');
        if ($Task->isValid()
            && $imageID != $this->obj->get('imageID')
        ) {
            throw new Exception(_('Cannot change image when in tasking'));
        }
        $this
            ->obj
            ->set('name', $name)
            ->set('description', $desc)
            ->set('imageID', $imageID)
            ->set('kernel', $kern)
            ->set('kernelArgs', $args)
            ->set('kernelDevice', $dev)
            ->set('init', $init)
            ->set('biosexit', $bte)
            ->set('efiexit', $ebte)
            ->set('productKey', $productKey);
        $primac = $this->obj->get('mac')->__toString();
        $setmac = $mac->__toString();
        if ($primac != $setmac) {
            $this->obj->addPriMAC($mac->__toString());
        }
        $addMACs = filter_input_array(
            INPUT_POST,
            array(
                'additionalMACs' => array(
                    'flags' => FILTER_REQUIRE_ARRAY
                )
            )
        );
        $addMACs = $addMACs['additionalMACs'];
        $addmacs = self::parseMacList($addMACs);
        unset($addMACs);
        $macs = array();
        foreach ((array)$addmacs as &$addmac) {
            if (!$addmac->isValid()) {
                continue;
            }
            $macs[] = $addmac->__toString();
            unset($addmac);
        }
        $removeMACs = array_diff(
            (array)self::getSubObjectIDs(
                'MACAddressAssociation',
                array(
                    'hostID' => $this->obj->get('id'),
                    'primary' => 0,
                    'pending' => 0
                ),
                'mac'
            ),
            $macs
        );
        $this
            ->obj
            ->addAddMAC($macs)
            ->removeAddMAC($removeMACs);
    }
    /**
     * Host printers display.
     *
     * @return void
     */
    public function hostPrinters()
    {
        $props = ' method="post" action="'
            . $this->formAction
            . '&tab=host-printers" ';

        // =========================================================
        // Printer Configuration
        echo '<!-- Printers -->';
        echo '<div class="box-group" id="printers">';
        echo '<div class="box box-info">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Host Printer Configuration');
        echo '</h4>';
        echo '</div>';
        echo '<div id="printerconf" class="">';
        echo '<form id="printer-config-form" class="form-horizontal"' . $props . '>';
        echo '<div class="box-body">';
        echo '<div class="radio">';
        echo '<label for="nolevel" data-toggle="tooltip" data-placement="left" '
            . 'title="'
            . _('This setting turns off all FOG Printer Management')
            . '. '
            . _('Although there are multiple levels already')
            . ' '
            . _('between host and global settings')
            . ', '
            . _('this is just another to ensure safety')
            . '.">';
        echo '<input type="radio" name="level" value="0" '
            . 'id="nolevel"'
            . (
                $this->obj->get('printerLevel') == 0 ?
                ' checked' :
                ''
            )
            . '/> ';
        echo _('No Printer Management');
        echo '</label>';
        echo '</div>';
        echo '<div class="radio">';
        echo '<label for="addlevel" data-toggle="tooltip" data-placement="left" '
            . 'title="'
            . _(
                'This setting only adds and removes '
                . 'printers that are managed by FOG. '
                . 'If the printer exists in printer '
                . 'management but is not assigned to a '
                . 'host, it will remove the printer if '
                . 'it exists on the unassigned host. '
                . 'It will add printers to the host '
                . 'that are assigned.'
            )
            . '">';
        echo '<input type="radio" name="level" value="1" '
            . 'id="addlevel"'
            . (
                $this->obj->get('printerLevel') == 1 ?
                ' checked' :
                ''
            )
            . '/> ';
        echo _('FOG Managed Printers');
        echo '</label>';
        echo '</div>';
        echo '<div class="radio">';
        echo '<label for="alllevel" data-toggle="tooltip" data-placement="left" '
            . 'title="'
            . _(
                'This setting will only allow FOG Assigned '
                . 'printers to be added to the host. Any '
                . 'printer that is not assigned will be '
                . 'removed including non-FOG managed printers.'
            )
            . '">';
        echo '<input type="radio" name="level" value="2" '
            . 'id="alllevel"'
            . (
                $this->obj->get('printerLevel') == 2 ?
                ' checked' :
                ''
            )
            . '/> ';
        echo _('Only Assigned Printers');
        echo '</label>';
        echo '</div>';
        echo '</div>';
        echo '<div class="box-footer">';
        echo '<button type="submit" name="levelup" class='
            . '"btn btn-primary" id="printer-config-send">'
            . _('Update')
            . '</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';

        // =========================================================
        // Associated Printers
        unset(
            $this->headerData,
            $this->templates,
            $this->attributes,
            $this->form,
            $this->data
        );

        $buttons = self::makeButton('printer-default', _('Update default'), 'btn btn-primary', $props);
        $buttons .= self::makeButton('printer-remove', _('Remove selected'), 'btn btn-danger', $props);
        $this->headerData = array(
            _('Default'),
            _('Printer Alias'),
            _('Printer Type'),
            _('Printer Associated')
        );
        $this->templates = array(
            '',
            '',
            '',
            ''
        );
        $this->attributes = array(
            array(
                'class' => 'col-md-1'
            ),
            array(),
            array(),
            array()
        );
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Update/Remove printers');
        echo '</h4>';
        echo '<div>';
        echo '<p class="help-block">';
        echo _('Changes will be automatically saved');
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '<div id="updateprinters" class="">';
        echo '<div class="box-body">';
        $this->render(12, 'host-printers-table', $buttons);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    /**
     * Host snapins.
     *
     * @return void
     */
    public function hostSnapins()
    {
        $props = ' method="post" action="'
            . $this->formAction
            . '&tab=host-snapins" ';

        echo '<!-- Snapins -->';
        echo '<div class="box-group" id="snapins">';
        // =================================================================
        // Associated Snapins
        unset(
            $this->headerData,
            $this->templates,
            $this->attributes,
            $this->form,
            $this->data
        );

        $buttons = self::makeButton('snapins-remove', _('Remove selected'), 'btn btn-danger', $props);

        $this->headerData = array(
            _('Snapin Name'),
            _('Snapin Created'),
            _('Snapin Associated')
        );
        $this->templates = array(
            '',
            '',
            ''
        );
        $this->attributes = array(
            array(),
            array(),
            array()
        );

        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Update/Remove Snapins');
        echo '</h4>';
        echo '<div>';
        echo '<p class="help-block">';
        echo _('Changes will be automatically saved');
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '<div id="updatesnapins" class="">';
        echo '<div class="box-body">';
        $this->render(12, 'host-snapins-table', $buttons);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // ========================================================
        // Add Snapins
        /*unset(
            $this->headerData,
            $this->templates,
            $this->attributes,
            $this->form,
            $this->data
        );

        $this->headerData = array(
            _('Snapin Name'),
            _('Snapin Created')
        );
        $this->templates = array(
            '<a href="?node=snapin&sub=edit&id=${snapin_id}">${snapin_name}</a>',
            '${snapin_created}'
        );
        $this->attributes = array(
            array(),
            array()
        );

        foreach ((array)$Snapins as &$Snapin) {
            if (!in_array($Snapin->id, $this->obj->get('snapinsnotinme'))) {
                continue;
            }
            $this->data[] = array(
                'id' => $Snapin->id,
                'snapin_id' => $Snapin->id,
                'snapin_name' => $Snapin->name,
                'snapin_created' => self::niceDate(
                    $Snapin->createdTime
                )->format('Y-m-d H:i:s')
            );
            unset($Snapin);
        }
        $buttons = self::makeButton('snapins-add', _('Add selected'), 'btn btn-default', $props);
        echo '<div class="box box-warning">';
        echo '<div class="box-header with-border">';
        echo '<h4 class="box-title">';
        echo _('Add Snapins');
        echo '</h4>';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '</div>';
        echo '<div id="addsnapins" class="">';
        self::$HookManager
            ->processEvent(
                'HOST_ADD_SNAPIN',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        echo '<div class="box-body">';
        $this->render(12, 'snapins-to-add-table', $buttons);
        echo '</div>';
        echo '<div class="box-footer">';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        unset(
            $this->headerData,
            $this->templates,
            $this->attributes,
            $this->form,
            $this->data
        );*/
    }
    /**
     * Display's the host service stuff
     *
     * @return void
     */
    public function hostService()
    {
        $props = ' method="post" action="'
            . $this->formAction
            . '&tab=host-service" ';

        echo '<!-- Modules/Service Settings -->';
        echo '<div class="box-group" id="modules">';
        // =============================================================
        // Associated Modules
        unset(
            $this->headerData,
            $this->templates,
            $this->attributes,
            $this->form,
            $this->data
        );

        // Buttons for this.
        $buttons = self::makeButton('modules-update', _('Update'), 'btn btn-primary', $props);
        $buttons .= self::makeButton('modules-enable', _('Enable All'), 'btn btn-success', $props);
        $buttons .= self::makeButton('modules-disable', _('Disable All'), 'btn btn-danger', $props);

        $this->headerData = array(
            _('Module Name'),
            _('Module Associated')
        );

        $this->templates = array(
            '',
            ''
        );
        $this->attributes = array(
            array(),
            array()
        );

        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Host module settings');
        echo ' --------- IN PROGRESS';
        echo '</h4>';
        echo '<div>';
        echo '<p class="help-block">';
        echo _('Modules disabled globally cannot be enabled here');
        echo '<br/>';
        echo _('Changes will automatically be saved');
        echo '</p>';
        echo '</div>';
        echo '</div>';
        echo '<div id="updatemodules" class="">';
        echo '<div class="box-body">';
        echo $this->render(12, 'modules-to-update', $buttons);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        return;

        // Old stuff, use if needed for extra elements (Display Manager, auto logout, etc...)

        $moduleName = self::getGlobalModuleStatus();
        $ModuleOn = $this->obj->get('modules');
        Route::listem('module');
        $Modules = json_decode(
            Route::getData()
        );
        $Modules = $Modules->data;
        foreach ((array)$Modules as &$Module) {
            switch ($Module->shortName) {
            case 'dircleanup':
            case 'greenfog':
            case 'usercleanup':
            case 'clientupdater':
                continue 2;
            }
            $this->data[] = array(
                'input' => sprintf(
                    '<div class="checkbox">'
                    . '<input id="%s"%stype="checkbox" name="modules[]" value="%s"'
                    . '%s%s/>'
                    . '</div>',
                    $Module->shortName,
                    (
                        ($moduleName[$Module->shortName]
                        || $moduleName[$Module->shortName])
                        && $Module->isDefault ?
                        ' class="checkboxes" ':
                        ''
                    ),
                    $Module->id,
                    (
                        in_array($Module->id, $ModuleOn) ?
                        ' checked' :
                        ''
                    ),
                    (
                        !$moduleName[$Module->shortName] ?
                        ' disabled' :
                        ''
                    ),
                    $Module->shortName
                ),
                'mod_name' => $Module->name,
            );
            // str_replace(
            //     '"',
            //     '\"',
            //     $Module->description
            // ),''
            unset($Module);
        }
        unset($moduleName, $ModuleOn);
        self::$HookManager
            ->processEvent(
                'HOST_EDIT_SERVICE',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        echo '<div class="box-group" id="printers">';
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo '<div class="box-tools pull-right">';
        echo self::$FOGCollapseBox;
        echo '</div>';
        echo '<h4 class="box-title">';
        echo _('Host module settings');
        echo ' --------- IN PROGRESS';
        echo '</h4>';
        echo '<div><p class="help-block">';
        echo _('Modules disabled globally cannot be enabled here');
        echo '</p></div>';
        echo '</div>';
        echo '</div>';
        echo '<div id="servicesettings" class="">';
        echo '<form class="form-horizontal" method="post" action="'
            . $this->formAction
            . '&tab=host-service">';
        echo '<div class="box-body table-responsive no-padding">';
        $this->render(12, 'host-service-modules-table', '', 'table table-hover', false);
        echo '</div>';
        echo '<div class="box-footer">';
        echo '<div class="btn-group">';
        echo '<button type="submit" class='
            . '"btn btn-primary" id="service-module-send">'
            . _('Update')
            . '</button>';
        echo '<button class='
            . '"btn btn-success" id="service-module-enable">'
            . _('Enable all')
            . '</button>';
        echo '<button  class='
            . '"btn btn-danger" id="service-module-disable">'
            . _('Disable all')
            . '</button>';
        echo '</div>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
        // echo '</div>';
        // echo '<label class="control-label col-xs-4" for="updatestatus">';
        // echo _('Update module configurations');
        // echo '</label>';
        // echo '<div class="col-xs-8">';
        // echo '<button type="submit" name="updatestatus" id="updatestatus" '
        //     . 'class="btn btn-info btn-block">';
        // echo _('Update');
        // echo '</button>';
        // echo '</div>';
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
        $this->attributes = array(
            array(
                'class' => 'col-xs-4'
            ),
            array(
                'class' => 'col-xs-4 form-group'
            ),
            array(
                'class' => 'col-xs-4'
            )
        );
        $this->templates = array(
            '${field}',
            '${input}',
            '${span}',
        );
        list(
            $refresh,
            $width,
            $height,
        ) = self::getSubObjectIDs(
            'Service',
            array(
                'name' => array(
                    'FOG_CLIENT_DISPLAYMANAGER_R',
                    'FOG_CLIENT_DISPLAYMANAGER_X',
                    'FOG_CLIENT_DISPLAYMANAGER_Y',
                )
            ),
            'description',
            false,
            'AND',
            'name',
            false,
            false
        );
        $names = array(
            'x' => array(
                'width',
                $width,
                _('Screen Width (in pixels)'),
            ),
            'y' => array(
                'height',
                $height,
                _('Screen Height (in pixels)'),
            ),
            'r' => array(
                'refresh',
                $refresh,
                _('Screen Refresh Rate (in Hz)'),
            )
        );
        foreach ($names as $name => &$get) {
            $this->data[] = array(
                'input' => sprintf(
                    '<div class="input-group">'
                    . '<input type="number" id="%s" name="%s" value="%s" '
                    . 'class="form-control"/>'
                    . '</div>',
                    $name,
                    $name,
                    $this->obj->getDispVals($get[0])
                ),
                'span' => sprintf(
                    '<div class="col-xs-2">'
                    . '<span class="icon fa fa-question fa-1x hand" '
                    . 'data-toggle="tooltip" data-placement="right" '
                    . 'title="%s"></span>'
                    . '</div>',
                    $get[1]
                ),
                'field' => '<label for="'
                . $name
                . '">'
                . $get[2]
                . '</label>',
            );
            unset($get);
        }
        self::$HookManager
            ->processEvent(
                'HOST_EDIT_DISPSERV',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        // echo '<div class="panel panel-info">';
        // echo '<div class="panel-heading text-center">';
        // echo '<h4 class="title">';
        // echo _('Host Screen Resolution');
        // echo '</h4>';
        // echo '</div>';
        // echo '<div class="panel-body">';
        // $this->render(12);
        // echo '<label class="control-label col-xs-4" for="updatedisplay">';
        // echo _('Update display resolution');
        // echo '</label>';
        // echo '<div class="col-xs-8">';
        // echo '<button type="submit" name="updatedisplay" id="updatedisplay" '
        //     . 'class="btn btn-info btn-block">';
        // echo _('Update');
        // echo '</button>';
        // echo '</div>';
        // echo '</div>';
        // echo '</div>';
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
        $this->attributes = array(
            array('class' => 'col-xs-4'),
            array('class' => 'col-xs-4 form-group'),
            array('class' => 'col-xs-4')
        );
        $this->templates = array(
            '${field}',
            '${input}',
            '${desc}',
        );
        $alodesc = self::getClass('Service')
            ->set('name', 'FOG_CLIENT_AUTOLOGOFF_MIN')
            ->load('name')
            ->get('description')
            ->set(
                'description',
                $alodesc
                . ' '
                . _('The minimum allowed time for autologout is 5 minutes.')
            )
            ->get('description');
        $this->data[] = array(
            'field' => '<label for="tme">'
            . _('Auto Log Out Time (in minutes)')
            . '</label>',
            'input' => '<div class="input-group">'
            . '<input type="number" name="tme" value="${value}" class='
            . '"form-control" id="tme"/>'
            . '</div>',
            'desc' => '<div class="col-xs-2">'
            . '<span class="icon fa fa-question fa-1x hand" '
            . 'data-toggle="tooltip" data-placement="right" '
            . 'title="${serv_desc}"></span>'
            . '</div>',
            'value' => $this->obj->getAlo(),
            'serv_desc' => $alodesc,
        );
        self::$HookManager
            ->processEvent(
                'HOST_EDIT_ALO',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        // echo '<div class="panel panel-info">';
        // echo '<div class="panel-heading text-center">';
        // echo '<h4 class="title">';
        // echo _('Host Auto Logout');
        // echo '</h4>';
        // echo '</div>';
        // echo '<div class="panel-body">';
        // $this->render(12);
        // echo '<label class="control-label col-xs-4" for="updatealo">';
        // echo _('Update auto-logout time');
        // echo '</label>';
        // echo '<div class="col-xs-8">';
        // echo '<button type="submit" name="updatealo" id="updatealo" '
        //     . 'class="btn btn-info btn-block">';
        // echo _('Update');
        // echo '</button>';
        // echo '</div>';
        // echo '</div>';
        // echo '</div>';
        // echo '</form>';
        // echo '</div>';
        // echo '</div>';
        // echo '</div>';
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
    }
    /**
     * Displays Host Inventory
     *
     * @return void
     */
    public function hostInventory()
    {
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
        $this->attributes = array(
            array('class' => 'col-xs-4'),
            array('class' => 'col-xs-8 form-group'),
        );
        $this->templates = array(
            '${field}',
            '${input}',
        );
        $cpus = array('cpuman', 'spuversion');
        foreach ($cpus as &$x) {
            $this->obj->get('inventory')
                ->set(
                    $x,
                    implode(
                        ' ',
                        array_unique(
                            explode(
                                ' ',
                                $this->obj->get('inventory')->get($x)
                            )
                        )
                    )
                )
                ->set('hostID', $this->obj->get('id'));
            unset($x);
        }
        $Inv = $this->obj->get('inventory');
        $puser = $Inv->get('primaryUser');
        $other1 = $Inv->get('other1');
        $other2 = $Inv->get('other2');
        $sysman = $Inv->get('sysman');
        $sysprod = $Inv->get('sysproduct');
        $sysver = $Inv->get('sysversion');
        $sysser = $Inv->get('sysserial');
        $systype = $Inv->get('systype');
        $sysuuid = $Inv->get('sysuuid');
        $biosven = $Inv->get('biosvendor');
        $biosver = $Inv->get('biosversion');
        $biosdate = $Inv->get('biosdate');
        $mbman = $Inv->get('mbman');
        $mbprod = $Inv->get('mbproductname');
        $mbver = $Inv->get('mbversion');
        $mbser = $Inv->get('mbserial');
        $mbast = $Inv->get('mbasset');
        $cpuman = $Inv->get('cpuman');
        $cpuver = $Inv->get('cpuversion');
        $cpucur = $Inv->get('cpucurrent');
        $cpumax = $Inv->get('cpumax');
        $mem = $Inv->getMem();
        $hdmod = $Inv->get('hdmodel');
        $hdfirm = $Inv->get('hdfirmware');
        $hdser = $Inv->get('hdserial');
        $caseman = $Inv->get('caseman');
        $casever = $Inv->get('caseversion');
        $caseser = $Inv->get('caseserial');
        $caseast = $Inv->get('caseasset');
        $fields = array(
            '<label for="pu">'
            . _('Primary User')
            . '</label>' => '<div class="input-group">'
            . '<input class="form-control" type="text" value="'
            . $puser
            . '" name="pu" id="pu"/>'
            . '</div>',
            '<label for="other1"/>'
            . _('Other Tag #1')
            . '</label>' => '<div class="input-group">'
            . '<input class="form-control" type="text" value="'
            . $other1
            . '" name="other1" id="other1"/>'
            . '</div>',
            '<label for="other2"/>'
            . _('Other Tag #2')
            . '</label>' => '<div class="input-group">'
            . '<input class="form-control" type="text" value="'
            . $other2
            . '" name="other2" id="other2"/>'
            . '</div>',
            _('System Manufacturer') => $sysman,
            _('System Product') => $sysprod,
            _('System Version') => $sysver,
            _('System Serial Number') => $sysser,
            _('System UUID') => $sysuuid,
            _('System Type') => $systype,
            _('BIOS Vendor') => $biosven,
            _('BIOS Version') => $biosver,
            _('BIOS Date') => $biosdate,
            _('Motherboard Manufacturer') => $mbman,
            _('Motherboard Product Name') => $mbprod,
            _('Motherboard Version') => $mbver,
            _('Motherboard Serial Number') => $mbser,
            _('Motherboard Asset Tag') => $mbast,
            _('CPU Manufacturer') => $cpuman,
            _('CPU Version') => $cpuver,
            _('CPU Normal Speed') => $cpucur,
            _('CPU Max Speed') => $cpumax,
            _('Memory') => $mem,
            _('Hard Disk Model') => $hdmod,
            _('Hard Disk Firmware') => $hdfirm,
            _('Hard Disk Serial Number') => $hdser,
            _('Chassis Manufacturer') => $caseman,
            _('Chassis Version') => $casever,
            _('Chassis Serial') => $caseser,
            _('Chassis Asset') => $caseast,
            '<label for="updateinv">'
            . _('Make Changes?')
            . '</label>' => '<button name="update" type="submit" class="'
            . 'btn btn-info btn-block" id="updateinv">'
            . _('Update')
            . '</button>'
        );
        $this->title = _('Host Hardware Inventory');
        if ($this->obj->get('inventory')->isValid()) {
            $rendered = self::formFields($fields);
        }
        self::$HookManager
            ->processEvent(
                'HOST_INVENTORY',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        echo '<!-- Inventory -->';
        echo '<div class="tab-pane fade" id="host-hardware-inventory">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">';
        echo $this->title;
        echo '</h4>';
        echo '</div>';
        echo '<div class="panel-body">';
        echo '<form class="form-horizontal" method="post" action="'
            . $this->formAction
            . '&tab=host-hardware-inventory">';
        $this->render(12);
        echo '</form>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
    }
    /**
     * Display Login History for Host.
     *
     * @return void
     */
    public function hostLoginHistory()
    {
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
        $this->headerData = array(
            _('Time'),
            _('Action'),
            _('Username'),
            _('Description')
        );
        $this->attributes = array(
            array(),
            array(),
            array(),
            array(),
        );
        $this->templates = array(
            '${user_time}',
            '${action}',
            '${user_name}',
            '${user_desc}',
        );
        $dte = filter_input(INPUT_GET, 'dte');
        if (!$dte) {
            self::niceDate()->format('Y-m-d');
        }
        $Dates = self::getSubObjectIDs(
            'UserTracking',
            array(
                'id' => $this->obj->get('users')
            ),
            'date'
        );
        if (count($Dates) > 0) {
            rsort($Dates);
            $dateSel = self::selectForm(
                'dte',
                $Dates,
                $dte,
                false,
                'loghist-date'
            );
        }
        Route::listem(
            'usertracking',
            'name',
            false,
            array(
                'hostID' => $this->obj->get('id'),
                'date' => $dte,
                'action' => array('', 0, 1)
            )
        );
        $UserLogins = json_decode(
            Route::getData()
        );
        $UserLogins = $UserLogins->usertrackings;
        $Data = array();
        foreach ((array)$UserLogins as &$UserLogin) {
            $time = self::niceDate(
                $UserLogin->datetime
            )->format('U');
            if (!isset($Data[$UserLogin->username])) {
                $Data[$UserLogin->username] = array();
            }
            if (array_key_exists('login', $Data[$UserLogin->username])) {
                if ($UserLogin->action > 0) {
                    $this->data[] = array(
                        'action' => _('Logout'),
                        'user_name' => $UserLogin->username,
                        'user_time' => (
                            self::niceDate()
                            ->setTimestamp($time - 1)
                            ->format('Y-m-d H:i:s')
                        ),
                        'user_desc' => _('Logout not found')
                        . '<br/>'
                        . _('Setting logout to one second prior to next login')
                    );
                    $Data[$UserLogin->username] = array();
                }
            }
            if ($UserLogin->action > 0) {
                $Data[$UserLogin->username]['login'] = true;
                $this->data[] = array(
                    'action' => _('Login'),
                    'user_name' => $UserLogin->username,
                    'user_time' => (
                        self::niceDate()
                        ->setTimestamp($time)
                        ->format('Y-m-d H:i:s')
                    ),
                    'user_desc' => $UserLogin->description
                );
            } elseif ($UserLogin->action < 1) {
                $this->data[] = array(
                    'action' => _('Logout'),
                    'user_name' => $UserLogin->username,
                    'user_time' => (
                        self::niceDate()
                        ->setTimestamp($time)
                        ->format('Y-m-d H:i:s')
                    ),
                    'user_desc' => $UserLogin->description
                );
                $Data[$UserLogin->username] = array();
            }
            unset($UserLogin);
        }
        self::$HookManager
            ->processEvent(
                'HOST_USER_LOGIN',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        echo '<!-- Login History -->';
        echo '<div class="tab-pane fade" id="host-login-history">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">';
        echo _('Host Login History');
        echo '</h4>';
        echo '</div>';
        echo '<div class="panel-body">';
        echo '<form class="form-horizontal" method="post" action="'
            . $this->formAction
            . '&tab=host-login-history">';
        if (count($Dates) > 0) {
            echo '<div class="form-group">';
            echo '<label class="control-label col-xs-4" for="dte">';
            echo _('View History For');
            echo '</label>';
            echo '<div class="col-xs-8">';
            echo $dateSel;
            echo '</div>';
            echo '</div>';
        }
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">';
        echo _('Selected Logins');
        echo '</h4>';
        echo '</div>';
        echo '<div class="panel-body">';
        $this->render(12);
        echo '</div>';
        echo '</div>';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">';
        echo _('History Graph');
        echo '</h4>';
        echo '</div>';
        echo '<div class="panel-body" id="login-history">';
        echo '</div>';
        echo '</div>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
    }
    /**
     * Display host imaging history.
     *
     * @return void
     */
    public function hostImageHistory()
    {
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
        $this->headerData = array(
            _('Engineer'),
            _('Imaged From'),
            _('Start'),
            _('End'),
            _('Duration'),
            _('Image'),
            _('Type'),
            _('State'),
        );
        $this->templates = array(
            '${createdBy}',
            sprintf(
                '<small>%s: ${group_name}</small><br/><small>%s: '
                . '${node_name}</small>',
                _('Storage Group'),
                _('Storage Node')
            ),
            '<small>${start_date}</small><br/><small>${start_time}</small>',
            '<small>${end_date}</small><br/><small>${end_time}</small>',
            '${duration}',
            '${image_name}',
            '${type}',
            '${state}',
        );
        $this->attributes = array(
            array(),
            array(),
            array(),
            array(),
            array(),
            array(),
            array(),
            array(),
        );
        Route::listem(
            'imaginglog',
            'name',
            false,
            array('hostID' => $this->obj->get('id'))
        );
        $Logs = json_decode(
            Route::getData()
        );
        $Logs = $Logs->imaginglogs;
        $imgTypes = array(
            'up' => _('Capture'),
            'down' => _('Deploy'),
        );
        foreach ((array)$Logs as &$Log) {
            $start = $Log->start;
            $finish = $Log->finish;
            if (!self::validDate($start)
                || !self::validDate($finish)
            ) {
                continue;
            }
            $diff = self::diff($start, $finish);
            $start = self::niceDate($start);
            $finish = self::niceDate($finish);
            $TaskIDs = self::getSubObjectIDs(
                'Task',
                array(
                    'checkInTime' => $Log->start,
                    'hostID' => $this->obj->get('id')
                )
            );
            $taskID = @max($TaskIDs);
            if (!$taskID) {
                continue;
            }
            Route::indiv('task', $taskID);
            $Task = json_decode(
                Route::getData()
            );
            $groupName = $Task->storagegroup->name;
            $nodeName = $Task->storagenode->name;
            $typeName = $Task->type->name;
            if (!$typeName) {
                $typeName = $Log->type;
            }
            if (in_array($typeName, array('up', 'down'))) {
                $typeName = $imgTypes[$typeName];
            }
            $stateName = $Task->state->name;
            unset($Task);
            $createdBy = (
                $log->createdBy ?:
                self::$FOGUser->get('name')
            );
            $Image = $Log->image;
            if (!$Image->id) {
                $imgName = $Image;
                $imgPath = _('N/A');
            } else {
                $imgName = $Image->name;
                $imgPath = $Image->path;
            }
            $this->data[] = array(
                'createdBy' => $createdBy,
                'group_name' => $groupName,
                'node_name' => $nodeName,
                'start_date' => $start->format('Y-m-d'),
                'start_time' => $start->format('H:i:s'),
                'end_date' => $finish->format('Y-m-d'),
                'end_time' => $finish->format('H:i:s'),
                'duration' => $diff,
                'image_name' => $imgName,
                'type' => $typeName,
                'state' => $stateName,
            );
            unset($Image, $Log);
        }
        self::$HookManager
            ->processEvent(
                'HOST_IMAGE_HIST',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        echo '<!-- Image History -->';
        echo '<div class="tab-pane fade" id="host-image-history">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">';
        echo _('Host Imaging History');
        echo '</h4>';
        echo '</div>';
        echo '<div class="panel-body">';
        $this->render(12);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
    }
    /**
     * Display host snapin history
     *
     * @return void
     */
    public function hostSnapinHistory()
    {
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
        $this->headerData = array(
            _('Snapin Name'),
            _('Start Time'),
            _('Complete'),
            _('Duration'),
            _('Return Code')
        );
        $this->templates = array(
            '${snapin_name}',
            '${snapin_start}',
            '${snapin_end}',
            '${snapin_duration}',
            '${snapin_return}'
        );
        $this->attributes = array(
            array(),
            array(),
            array(),
            array(),
            array()
        );
        $SnapinJobIDs = self::getSubObjectIDs(
            'SnapinJob',
            array(
                'hostID' => $this->obj->get('id')
            )
        );
        $doneStates = array(
            self::getCompleteState(),
            self::getCancelledState()
        );
        Route::listem(
            'snapintask',
            'name',
            false,
            array('jobID' => $SnapinJobIDs)
        );
        $SnapinTasks = json_decode(
            Route::getData()
        );
        $SnapinTasks = $SnapinTasks->snapintasks;
        foreach ((array)$SnapinTasks as &$SnapinTask) {
            $Snapin = $SnapinTask->snapin;
            $start = self::niceDate($SnapinTask->checkin);
            $end = self::niceDate($SnapinTask->complete);
            if (!self::validDate($start)) {
                continue;
            }
            if (!in_array($SnapinTask->stateID, $doneStates)) {
                $diff = _('Snapin task not completed');
            } elseif (!self::validDate($end)) {
                $diff = _('No complete time recorded');
            } else {
                $diff = self::diff($start, $end);
            }
            $this->data[] = array(
                'snapin_name' => $Snapin->name,
                'snapin_start' => $start->format('Y-m-d H:i:s'),
                'snapin_end' => sprintf(
                    '<span data-toggle="tooltip" data-placement="left" '
                    . 'class="icon" title="%s">%s</span>',
                    $end->format('Y-m-d H:i:s'),
                    $SnapinTask->state->name
                ),
                'snapin_duration' => $diff,
                'snapin_return'=> $SnapinTask->return,
            );
            unset($SnapinTask);
        }
        self::$HookManager
            ->processEvent(
                'HOST_SNAPIN_HIST',
                array(
                    'headerData' => &$this->headerData,
                    'data' => &$this->data,
                    'templates' => &$this->templates,
                    'attributes' => &$this->attributes
                )
            );
        echo '<div class="tab-pane fade" id="host-snapin-history">';
        echo '<div class="panel panel-info">';
        echo '<div class="panel-heading text-center">';
        echo '<h4 class="title">';
        echo _('Host Snapin History');
        echo '</h4>';
        echo '</div>';
        echo '<div class="panel-body">';
        $this->render(12);
        echo '</div>';
        echo '</div>';
        echo '</div>';
        unset(
            $this->data,
            $this->form,
            $this->headerData,
            $this->templates,
            $this->attributes
        );
    }
    /**
     * Edits an existing item.
     *
     * @return void
     */
    public function edit()
    {
        $this->title = sprintf(
            '%s: %s',
            _('Edit'),
            $this->obj->get('name')
        );
        $approve = filter_input(INPUT_GET, 'approveHost');
        if ($approve) {
            $this
                ->obj
                ->set(
                    'pending',
                    0
                );
            if ($this->obj->save()) {
                self::setMessage(_('Host approved'));
            } else {
                self::setMessage(_('Host approval failed.'));
            }
            self::redirect(
                '?node='
                . $this->node
                . '&sub=edit&id='
                . $this->obj->get('id')
            );
        }
        if ($this->obj->get('pending')) {
            echo '<div class="panel panel-info">';
            echo '<div class="panel-heading">';
            echo '<h4 class="title">';
            echo _('Approve Host');
            echo '</h4>';
            echo '</div>';
            echo '<div class="panel-body">';
            echo '<a href="'
                . $this->formAction
                . '&approveHost=1">'
                . _('Approve this host?')
                . '</a>';
            echo '</div>';
            echo '</div>';
        }
        $confirmMac = filter_input(
            INPUT_GET,
            'confirmMAC'
        );
        $approveAll = filter_input(
            INPUT_GET,
            'approveAll'
        );
        if ($confirmMac) {
            try {
                $this->obj->addPendtoAdd($confirmMac);
                if ($this->obj->save()) {
                    $msg = _('MAC')
                        . ': '
                        . $confirmMac
                        . ' '
                        . _('Approved')
                        . '!';
                    self::setMessage($msg);
                    unset($msg);
                }
            } catch (Exception $e) {
                self::setMessage($e->getMessage());
            }
            self::redirect(
                '?node='
                . $this->node
                . '&sub=edit&id='
                . $this->obj->get('id')
            );
        } elseif ($approveAll) {
            self::getClass('MACAddressAssociationManager')
                ->update(
                    array(
                        'hostID' => $this->obj->get('id')
                    ),
                    '',
                    array(
                        'pending' => 0
                    )
                );
            $msg = sprintf(
                '%s.',
                _('All Pending MACs approved')
            );
            self::setMessage($msg);
            self::redirect(
                sprintf(
                    '?node=%s&sub=edit&id=%s',
                    $this->node,
                    (int)$_POST['id']
                )
            );
        }
        $tabData = array();
        // General
        $tabData[] = array(
            'name' => _('General'),
            'id' => 'host-general',
            'generator' => function() {
                $this->hostGeneral();
            }
        );
        // Active Directory
        $tabData[] = array(
            'name' =>  _('Active Directory'),
            'id' => 'host-active-directory',
            'generator' => function() {
                $this->adFieldsToDisplay(
                    $this->obj->get('useAD'),
                    $this->obj->get('ADDomain'),
                    $this->obj->get('ADOU'),
                    $this->obj->get('ADUser'),
                    $this->obj->get('ADPass'),
                    $this->obj->get('enforce')
                );
            }
        );
        // Tasks
        if (!$this->obj->get('pending')) {
            $tabData[] = array(
                'name' =>  _('Tasks'),
                'id' => 'host-tasks',
                'generator' => function() {
                    $this->basictasksOptions();
                }
            );
        }
        // Printers
        $tabData[] = array(
            'name' => _('Printers'),
            'id' => 'host-printers',
            'generator' => function() {
                $this->hostPrinters();
            }
        );
        // Snapins
        $tabData[] = array(
            'name' => _('Snapins'),
            'id' => 'host-snapins',
            'generator' => function() {
                $this->hostSnapins();
            }
        );

        // Service
        $tabData[] = array(
            'name' => _('Service Settings'),
            'id' => 'host-service',
            'generator' => function() {
                $this->hostService();
            }
        );

        // Power Management
        /*$tabData[] = array(
            'name' => _('Power Management'),
            'id' => 'host-powermanagement',
            'generator' => function() {
                $this->hostPMDisplay();
            }
        );

        // Inventory
        $tabData[] = array(
            'name' => _('Inventory'),
            'id' => 'host-inventory',
            'generator' => function() {
                $this->hostInventory();
            }
        );

        // Login History
        $tabData[] = array(
            'name' => _('Login History'),
            'id' => 'host-login-history',
            'generator' => function() {
                $this->hostLoginHistory();
            }
        );

        // Image History
        $tabData[] = array(
            'name' => _('Image History'),
            'id' => 'host-image-history',
            'generator' => function() {
                $this->hostImageHistory();
            }
        );

        // Snapin History
        $tabData[] = array(
            'name' => _('Snapin History'),
            'id' => 'host-snapin-history',
            'generator' => function() {
                $this->hostSnapinHistory();
            }
        );*/

        /**
         * These need to be worked yet.
         *
        $this->hostPMDisplay();
        $this->hostInventory();
        $this->hostLoginHistory();
        $this->hostImageHistory();
        $this->hostSnapinHistory();
         */
        echo self::tabFields($tabData);
    }
    /**
     * Host active directory post element.
     *
     * @return void
     */
    public function hostADPost()
    {
        $useAD = isset($_POST['domain']);
        $domain = trim(
            filter_input(
                INPUT_POST,
                'domainname'
            )
        );
        $ou = trim(
            filter_input(
                INPUT_POST,
                'ou'
            )
        );
        $user = trim(
            filter_input(
                INPUT_POST,
                'domainuser'
            )
        );
        $pass = trim(
            filter_input(
                INPUT_POST,
                'domainpassword'
            )
        );
        $enforce = isset($_POST['enforcesel']);
        $this->obj->setAD(
            $useAD,
            $domain,
            $ou,
            $user,
            $pass,
            true,
            true,
            $productKey,
            $enforce
        );
    }
    /**
     * Host power management post.
     *
     * @return void
     */
    public function hostPMPost()
    {
        $onDemand = (int)isset($_POST['onDemand']);
        $items = array();
        $flags = array('flags' => FILTER_REQUIRE_ARRAY);
        if (isset($_POST['pmupdate'])) {
            $items = filter_input_array(
                INPUT_POST,
                array(
                    'scheduleCronMin' => $flags,
                    'scheduleCronHour' => $flags,
                    'scheduleCronDOM' => $flags,
                    'scheduleCronMonth' => $flags,
                    'scheduleCronDOW' => $flags,
                    'pmid' => $flags,
                    'action' => $flags
                )
            );
            extract($items);
            if (!$action) {
                throw new Exception(
                    _('You must select an action to perform')
                );
            }
            $items = array();
            foreach ((array)$pmid as $index => &$pm) {
                $onDemandItem = array_search(
                    $pm,
                    $onDemand
                );
                $items[] = array(
                    $pm,
                    $this->obj->get('id'),
                    $scheduleCronMin[$index],
                    $scheduleCronHour[$index],
                    $scheduleCronDOM[$index],
                    $scheduleCronMonth[$index],
                    $scheduleCronDOW[$index],
                    $onDemandItem !== -1
                    && $onDemand[$onDemandItem] === $pm ?
                    1 :
                    0,
                    $action[$index]
                );
                unset($pm);
            }
            self::getClass('PowerManagementManager')
                ->insertBatch(
                    array(
                        'id',
                        'hostID',
                        'min',
                        'hour',
                        'dom',
                        'month',
                        'dow',
                        'onDemand',
                        'action'
                    ),
                    $items
                );
        }
        if (isset($_POST['pmsubmit'])) {
            $min = trim(
                filter_input(
                    INPUT_POST,
                    'scheduleCronMin'
                )
            );
            $hour = trim(
                filter_input(
                    INPUT_POST,
                    'scheduleCronHour'
                )
            );
            $dom = trim(
                filter_input(
                    INPUT_POST,
                    'scheduleCronDOM'
                )
            );
            $month = trim(
                filter_input(
                    INPUT_POST,
                    'scheduleCronMonth'
                )
            );
            $dow = trim(
                filter_input(
                    INPUT_POST,
                    'scheduleCronDOW'
                )
            );
            $action = trim(
                filter_input(
                    INPUT_POST,
                    'action'
                )
            );
            if ($onDemand && $action === 'wol') {
                $this->obj->wakeOnLAN();
                return;
            }
            self::getClass('PowerManagement')
                ->set('hostID', $this->obj->get('id'))
                ->set('min', $min)
                ->set('hour', $hour)
                ->set('dom', $dom)
                ->set('month', $month)
                ->set('dow', $dow)
                ->set('onDemand', $onDemand)
                ->set('action', $action)
                ->save();
        }
        if (isset($_POST['pmdelete'])) {
            $pmid = filter_input_array(
                INPUT_POST,
                array(
                    'rempowermanagements' => $flags
                )
            );
            $pmid = $pmid['rempowermanagements'];
            self::getClass('PowerManagementManager')
                ->destroy(
                    array(
                        'id' => $pmid
                    )
                );
        }
    }
    /**
     * Host printer post.
     *
     * @return void
     */
    public function hostPrinterPost()
    {
        if (isset($_POST['levelup'])) {
            $level = filter_input(INPUT_POST, 'level');
            $this->obj->set('printerLevel', $level);
        }
        if (isset($_POST['updateprinters'])) {
            $printers = filter_input_array(
                INPUT_POST,
                array(
                    'printer' => array(
                        'flags' => FILTER_REQUIRE_ARRAY
                    )
                )
            );
            $printers = $printers['printer'];
            if (count($printers) > 0) {
                $this->obj->addPrinter($printers);
            }
        }
        if (isset($_POST['defaultsel'])) {
            $this->obj->updateDefault(
                filter_input(
                    INPUT_POST,
                    'default'
                ),
                isset($_POST['default'])
            );
        }
        if (isset($_POST['printdel'])) {
            $printers = filter_input_array(
                INPUT_POST,
                array(
                    'printerRemove' => array(
                        'flags' => FILTER_REQUIRE_ARRAY
                    )
                )
            );
            $printers = $printers['printerRemove'];
            if (count($printers) > 0) {
                $this->obj->removePrinter($printers);
            }
        }
    }
    /**
     * Host snapin post
     *
     * @return void
     */
    public function hostSnapinPost()
    {
        if (isset($_POST['updatesnapins'])) {
            $snapins = filter_input_array(
                INPUT_POST,
                array(
                    'snapin' => array(
                        'flags' => FILTER_REQUIRE_ARRAY
                    )
                )
            );
            $snapins = $snapins['snapin'];
            if (count($snapins) > 0) {
                $this
                    ->obj
                    ->addSnapin($snapins);
            }
        }
        if (isset($_POST['snapdel'])) {
            $snapins = filter_input_array(
                INPUT_POST,
                array(
                    'snapinRemove' => array(
                        'flags' => FILTER_REQUIRE_ARRAY
                    )
                )
            );
            $snapins = $snapins['snapinRemove'];
            if (count($snapins) > 0) {
                $this
                    ->obj
                    ->removeSnapin(
                        $snapins
                    );
            }
        }
    }
    /**
     * Update the actual thing.
     *
     * @return void
     */
    public function hostServicePost()
    {
        $x = filter_input(INPUT_POST, 'x');
        $y = filter_input(INPUT_POST, 'y');
        $r = filter_input(INPUT_POST, 'r');
        $tme = filter_input(INPUT_POST, 'tme');
        if (!$tme
            || !is_numeric($tme)
            || (is_numeric($tme) && $tme < 5)
        ) {
            $tme = 0;
        }
        $modOn = filter_input_array(
            INPUT_POST,
            array(
                'modules' => array(
                    'flags' => FILTER_REQUIRE_ARRAY
                )
            )
        );
        $modOn = $modOn['modules'];
        $modOff = self::getSubObjectIDs(
            'Module',
            array(
                'id' => $modOn
            ),
            'id',
            true
        );
        $this->obj->addModule($modOn);
        $this->obj->removeModule($modOff);
        $this->obj->setDisp($x, $y, $r);
        $this->obj->setAlo($tme);
    }
    /**
     * Updates the host when form is submitted
     *
     * @return void
     */
    public function editPost()
    {
        header('Content-type: application/json');

        self::$HookManager->processEvent(
            'HOST_EDIT_POST',
            array('Host' => &$this->obj)
        );
        try {
            global $tab;
            switch ($tab) {
            case 'host-general':
                $this->hostGeneralPost();
                break;
            case 'host-active-directory':
                $this->hostADPost();
                break;
            case 'host-powermanagement':
                $this->hostPMPost();
                break;
            case 'host-printers':
                $this->hostPrinterPost();
                break;
            case 'host-snapins':
                $this->hostSnapinPost();
                break;
            case 'host-service':
                $this->hostServicePost();
                break;
            case 'host-hardware-inventory':
                $pu = filter_input(INPUT_POST, 'pu');
                $other1 = filter_input(INPUT_POST, 'other1');
                $other2 = filter_input(INPUT_POST, 'other2');
                if (isset($_POST['update'])) {
                    $this->obj
                        ->get('inventory')
                        ->set('primaryUser', $pu)
                        ->set('other1', $other1)
                        ->set('other2', $other2)
                        ->set('hostID', $this->obj->get('id'))
                        ->save();
                }
                break;
            case 'host-login-history':
                $dte = filter_input(INPUT_POST, 'dte');
                self::redirect(
                    '?node='
                    . $this->node
                    . '&sub=edit&id='
                    . $this->obj->get('id')
                    . '&dte='
                    . $dte
                    . '#'
                    . $tab
                );
                break;
            }
            if (!$this->obj->save()) {
                throw new Exception(_('Host Update Failed'));
            }
            $this->obj->setAD();
            if ($tab == 'host-general') {
                $igstuff = filter_input_array(
                    INPUT_POST,
                    array(
                        'igimage' => array(
                            'flags' => FILTER_REQUIRE_ARRAY
                        ),
                        'igclient' => array(
                            'flags' => FILTER_REQUIRE_ARRAY
                        )
                    )
                );
                $igimage = $igstuff['igimage'];
                $igclient = $igstuff['igclient'];
                $this->obj->ignore($igimage, $igclient);
            }
            $hook = 'HOST_EDIT_SUCCESS';
            $msg = json_encode(
                array(
                    'msg' => _('Host updated!'),
                    'title' => _('Host Update Success')
                )
            );
        } catch (Exception $e) {
            http_response_code(400);
            $hook = 'HOST_EDIT_FAIL';
            $msg = json_encode(
                array(
                    'error' => $e->getMessage(),
                    'title' => _('Host Update Fail')
                )
            );
        }
        self::$HookManager
            ->processEvent(
                $hook,
                array('Host' => &$this->obj)
            );
        echo $msg;
        exit;
    }
    /**
     * Saves host to a selected or new group depending on action.
     *
     * @return void
     */
    public function saveGroup()
    {
        $group = filter_input(INPUT_POST, 'group');
        $newgroup = filter_input(INPUT_POST, 'group_new');
        $hostids = filter_input(
            INPUT_POST,
            'hostIDArray'
        );
        $hostids = array_values(
            array_filter(
                array_unique(
                    explode(',', $hostids)
                )
            )
        );
        try {
            $Group = new Group($group);
            if ($newgroup) {
                $Group
                    ->set('name', $newgroup)
                    ->load('name');
            }
            $Group->addHost($hostids);
            if (!$Group->save()) {
                throw new Exception(_('Failed to create new Group'));
            }
            $msg = json_encode(
                array(
                    'msg' => _('Successfully added selected hosts to the group!'),
                    'title' => _('Host Add to Group Success')
                )
            );
        } catch (Exception $e) {
            $msg = json_encode(
                array(
                    'error' => $e->getMessage(),
                    'title' => _('Host Add to Group Fail')
                )
            );
        }
        echo $msg;
        exit;
    }
    /**
     * Gets the host user tracking info.
     *
     * @return void
     */
    public function hostlogins()
    {
        $date = filter_input(INPUT_GET, 'dte');
        $MainDate = self::niceDate($date)
            ->getTimestamp();
        $MainDate_1 = self::niceDate($date)
            ->modify('+1 day')
            ->getTimestamp();
        Route::listem('UserTracking');
        $UserTracks = json_decode(
            Route::getData()
        );
        $UserTracks = $UserTracks->usertrackings;
        $data = null;
        $Data = array();
        foreach ((array)$UserTracks as &$Login) {
            $ldate = self::niceDate($Login->date)
                ->format('Y-m-d');
            if ($Login->hostID != $this->obj->get('id')
                || $ldate != $date
                || !in_array($Login->action, array('', 0, 1))
            ) {
                continue;
            }
            $time = self::niceDate($Login->datetime);
            $Data[$Login->username] = array(
                'user' => $Login->username,
                'min' => $MainDate,
                'max' => $MainDate_1
            );
            if (array_key_exists('login', $Data[$Login->username])) {
                if ($Login->action > 0) {
                    $Data[$Login->username]['logout'] = (int)$time - 1;
                    $data[] = $Data[$Login->username];
                } elseif ($Login->action < 1) {
                    $Data[$Login->username]['logout'] = (int)$time;
                    $data[] = $Data[$Login->username];
                }
                $Data[$Login->username] = array(
                    'user' => $Login->username,
                    'min' => $MainDate,
                    'max' => $MainDate_1
                );
            }
            if ($Login->action > 0) {
                $Data[$Login->username]['login'] = (int)$time;
            }
            unset($Login);
        }
        unset($UserTracks);
        echo json_encode($data);
        exit;
    }
    /**
     * Presents the printers list table.
     *
     * @return void
     */
    public function getPrintersList()
    {
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );

        $where = "`hosts`.`hostID` = '"
            . $this->obj->get('id')
            . "'";

        // Workable queries
        $printersSqlStr = "SELECT `%s`,"
            . "IF(`paHostID` IS NULL OR `paHostID` = '0' OR `paHostID` = '', 'dissociated', 'associated') AS `paHostID`,`paIsDefault`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `printerAssoc`
            ON `printers`.`pID` = `printerAssoc`.`paPrinterID`
            AND `hosts`.`hostID` = `printerAssoc`.`paHostID`
            %s
            %s
            %s";

        $printersFilterStr = "SELECT COUNT(`%s`),"
            . "IF(`paHostID` IS NULL OR `paHostID` = '0' OR `paHostID` = '', 'dissociated', 'associated') AS `paHostID`,`paIsDefault`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `printerAssoc`
            ON `printers`.`pID` = `printerAssoc`.`paPrinterID`
            AND `hosts`.`hostID` = `printerAssoc`.`paHostID`
            %s";

        $printersTotalStr = "SELECT COUNT(`%s`)
            FROM `%s`";

        foreach (self::getClass('PrinterManager')
            ->getColumns() as $common => &$real
        ) {
            $columns[] = array('db' => $real, 'dt' => $common);
            unset($real);
        }
        $columns[] = array('db' => 'paIsDefault', 'dt' => 'isDefault');
        $columns[] = array('db' => 'paHostID', 'dt' => 'association');
        echo json_encode(
            FOGManagerController::complex(
                $pass_vars,
                'printers',
                'pID',
                $columns,
                $printersSqlStr,
                $printersFilterStr,
                $printersTotalStr,
                $where
            )
        );
        exit;
    }
    /**
     * Presents the snapins list table.
     *
     * @return void
     */
    public function getSnapinsList()
    {
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );

        $where = "`hosts`.`hostID` = '"
            . $this->obj->get('id')
            . "'";

        // Workable queries
        $snapinsSqlStr = "SELECT `%s`,"
            . "IF(`saHostID` IS NULL OR `saHostID` = '0' OR `saHostID` = '', 'dissociated', 'associated') AS `saHostID`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `snapinAssoc`
            ON `snapins`.`sID` = `snapinAssoc`.`saSnapinID`
            AND `hosts`.`hostID` = `snapinAssoc`.`saHostID`
            %s
            %s
            %s";

        $snapinsFilterStr = "SELECT COUNT(`%s`),"
            . "IF(`saHostID` IS NULL OR `saHostID` = '0' OR `saHostID` = '', 'dissociated', 'associated') AS `saHostID`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `snapinAssoc`
            ON `snapins`.`sID` = `snapinAssoc`.`saSnapinID`
            AND `hosts`.`hostID` = `snapinAssoc`.`saHostID`
            %s";

        $snapinsTotalStr = "SELECT COUNT(`%s`)
            FROM `%s`";

        foreach (self::getClass('SnapinManager')
            ->getColumns() as $common => &$real
        ) {
            $columns[] = array('db' => $real, 'dt' => $common);
            unset($real);
        }
        $columns[] = array('db' => 'saHostID', 'dt' => 'association');
        echo json_encode(
            FOGManagerController::complex(
                $pass_vars,
                'snapins',
                'sID',
                $columns,
                $snapinsSqlStr,
                $snapinsFilterStr,
                $snapinsTotalStr,
                $where
            )
        );
        exit;
    }
    /**
     * Returns the module list as well as the associated
     * for the host being edited.
     *
     * @return void
     */
    public function getModulesList()
    {
        parse_str(
            file_get_contents('php://input'),
            $pass_vars
        );
        $moduleName = self::getGlobalModuleStatus();
        $keys = [];
        foreach ((array)$moduleName as $short_name => $bool) {
            if ($bool) {
                $keys[] = $short_name;
            }
        }

        $where = "`hosts`.`hostID` = '"
            . $this->obj->get('id')
            . "' AND `modules`.`short_name` "
            . "NOT IN ('clientupdater','dircleanup','greenfog','usercleanup') "
            . "AND `modules`.`short_name` IN ('" . implode("','", $keys) . "')";

        // Workable queries
        $modulesSqlStr = "SELECT `%s`,"
            . "IF(`msHostID` IS NULL OR `msHostID` = '0' OR `msHostID` = '', 'dissociated', 'associated') AS `msHostID`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `moduleStatusByHost`
            ON `modules`.`id` = `moduleStatusByHost`.`msModuleID`
            AND `hosts`.`hostID` = `moduleStatusByHost`.`msHostID`
            %s
            %s
            %s";

        $modulesFilterStr = "SELECT COUNT(`%s`),"
            . "IF(`msHostID` IS NULL OR `msHostID` = '0' OR `msHostID` = '', 'dissociated', 'associated') AS `msHostID`,`hostID`
            FROM `%s`
            CROSS JOIN `hosts`
            LEFT OUTER JOIN `moduleStatusByHost`
            ON `modules`.`id` = `moduleStatusByHost`.`msModuleID`
            AND `hosts`.`hostID` = `moduleStatusByHost`.`msHostID`
            %s";

        $modulesTotalStr = "SELECT COUNT(`%s`)
            FROM `%s` WHERE `modules`.`short_name`
            NOT IN ('clientupdater','dircleanup','greenfog','usercleanup')";

        foreach (self::getClass('ModuleManager')
            ->getColumns() as $common => &$real
        ) {
            $columns[] = array('db' => $real, 'dt' => $common);
            unset($real);
        }
        $columns[] = array('db' => 'msHostID', 'dt' => 'association');
        echo json_encode(
            FOGManagerController::complex(
                $pass_vars,
                'modules',
                'id',
                $columns,
                $modulesSqlStr,
                $modulesFilterStr,
                $modulesTotalStr,
                $where
            )
        );
        exit;
    }
}
