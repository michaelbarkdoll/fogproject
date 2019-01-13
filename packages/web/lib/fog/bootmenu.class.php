<?php
/**
 * Boot menu for the fog pxe system
 *
 * PHP Version 5
 *
 * @category Bootmenu
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
/**
 * Boot menu for the fog pxe system
 *
 * @category Bootmenu
 * @package  FOGProject
 * @author   Tom Elliott <tommygunsster@gmail.com>
 * @license  http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link     https://fogproject.org
 */
class BootMenu extends FOGBase
{
    /**
     * The kernel string
     *
     * @var string
     */
    private $_kernel;
    /**
     * The init string
     *
     * @var string
     */
    private $_initrd;
    /**
     * The boot url string
     *
     * @var string
     */
    private $_booturl;
    /**
     * The mem disk string
     *
     * @var string
     */
    private $_memdisk;
    /**
     * The memtest string
     *
     * @var string
     */
    private $_memtest;
    /**
     * The web string
     *
     * @var string
     */
    private $_web;
    /**
     * The default choice
     *
     * @var string
     */
    private $_defaultChoice;
    /**
     * The boot exit type
     *
     * @var string
     */
    private $_bootexittype;
    /**
     * The log level string
     *
     * @var string
     */
    private $_loglevel;
    /**
     * The storage information string
     *
     * @var string
     */
    private $_storage;
    /**
     * The shutdown string
     *
     * @var string
     */
    private $_shutdown;
    /**
     * The path string
     *
     * @var string
     */
    private $_path;
    /**
     * The hidden menu storage
     *
     * @var bool
     */
    private $_hiddenmenu;
    /**
     * The timeout of the menu
     *
     * @var int
     */
    private $_timeout;
    /**
     * The key sequance storage
     *
     * @var string
     */
    private $_KS;
    /**
     * The selectable exit types
     *
     * @var array
     */
    private static $_exitTypes = [];
    /**
     * Initializes the boot menu class
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $grubChain = 'chain -ar ${boot-url}/service/ipxe/grub.exe '
            . '--config-file="%s"';
        $sanboot = 'sanboot --no-describe --drive 0x80';
        $grub = [
            'basic' => sprintf(
                $grubChain,
                'rootnoverify (hd0);chainloader +1'
            ),
            '1cd' => sprintf(
                $grubChain,
                'cdrom --init;map --hook;root (cd0);chainloader (cd0)"'
            ),
            '1fw' => sprintf(
                $grubChain,
                'find --set-root /BOOTMGR;chainloader /BOOTMGR"'
            )
        ];
        $refindfile = 'refind_x64.efi';
        if (file_exists(BASEPATH . '/service/ipxe/refind.efi')) {
            $refindfile = 'refind.efi';
        }
        $refind = sprintf(
            'imgfetch ${boot-url}/service/ipxe/refind.conf%s'
            . 'chain -ar ${boot-url}/service/ipxe/%s',
            "\n",
            $refindfile
        );
        if (false !== stripos($_REQUEST['arch'], 'arm')) {
            $grubChain = 'chain -ar ${boot-url}/service/ipxe/grub_aa64.exe '
                . '--configfile="%s"';
            $refind = sprintf(
                'imgfetch ${boot-url}/service/ipxe/refind_aa64.conf%s'
                . 'chain -ar ${boot-url}/service/ipxe/refind_aa64.efi',
                "\n"
            );
        }
        if (false !== stripos($_REQUEST['arch'], 'i386')) {
            // use i386 boot loaders instead.
            if ('refind_x64.efi' === $refindfile) {
                $refindfile = 'refind_ia32.efi';
            }
            $refind = sprintf(
                'imgfetch ${boot-url}/service/ipxe/refind.conf%s'
                . 'chain -ar ${boot-url}/service/ipxe/%s',
                "\n",
                $refindfile
            );
        }
        self::$_exitTypes = [
            'sanboot' => $sanboot,
            'grub' => $grub['basic'],
            'grub_first_hdd' => $grub['basic'],
            'grub_first_cdrom' => $grub['1cd'],
            'grub_first_found_windows' => $grub['1fw'],
            'refind_efi' => $refind,
            'exit' => 'exit',
        ];
        list(
            $webserver,
            $curroot
        ) = self::getSetting(['FOG_WEB_HOST','FOG_WEB_ROOT']);
        $curroot = '/fog/';
        $this->_web = sprintf('%s://%s%s', self::$httpproto, $webserver, $curroot);
        $Send['booturl'] = [
            '#!ipxe',
            "set fog-ip $webserver",
            sprintf('set fog-webroot %s', basename($curroot)),
            'set boot-url '
            . self::$httpproto
            . '://${fog-ip}/${fog-webroot}',
        ];
        $this->_parseMe($Send);
        if (self::$Host->isValid()) {
            if (!self::$Host->get('inventory')->get('sysuuid')) {
                self::$Host
                    ->get('inventory')
                    ->set('sysuuid', $_REQUEST['sysuuid'])
                    ->set('hostID', self::$Host->get('id'))
                    ->save();
            }
        }
        $host_field_test = 'biosexit';
        $global_field_test = 'FOG_BOOT_EXIT_TYPE';
        if ($_REQUEST['platform'] == 'efi') {
            $host_field_test = 'efiexit';
            $global_field_test = 'FOG_EFI_BOOT_EXIT_TYPE';
        }
        Route::ids(
            'storagenode',
            [
                'isEnabled' => 1,
                'isMaster' => 1
            ]
        );
        $StorageNodeID = @min(json_decode(Route::getData(), true));
        $StorageNode = new StorageNode($StorageNodeID);
        $serviceNames = [
            'FOG_EFI_BOOT_EXIT_TYPE',
            'FOG_KERNEL_ARGS',
            'FOG_KERNEL_DEBUG',
            'FOG_KERNEL_LOGLEVEL',
            'FOG_KERNEL_RAMDISK_SIZE',
            'FOG_KEYMAP',
            'FOG_KEY_SEQUENCE',
            'FOG_MEMTEST_KERNEL',
            'FOG_PXE_BOOT_IMAGE',
            'FOG_PXE_BOOT_IMAGE_32',
            'FOG_PXE_HIDDENMENU_TIMEOUT',
            'FOG_PXE_MENU_HIDDEN',
            'FOG_PXE_MENU_TIMEOUT',
            'FOG_TFTP_PXE_KERNEL',
            'FOG_TFTP_PXE_KERNEL_32',
        ];
        list(
            $exit,
            $kernelArgs,
            $kernelDebug,
            $kernelLogLevel,
            $kernelRamDisk,
            $keymap,
            $keySequence,
            $memtest,
            $imagefile,
            $init_32,
            $hiddenTimeout,
            $hiddenmenu,
            $menuTimeout,
            $bzImage,
            $bzImage32
        ) = self::getSetting($serviceNames);
        $memdisk = 'memdisk';
        $loglevel = $kernelLogLevel;
        $ramsize = $kernelRamDisk;
        $timeout = (
            $hiddenmenu > 0 && !$_REQUEST['menuAccess'] ?
            $hiddenTimeout :
            $menuTimeout
        ) * 1000;
        $keySequence = (
            $hiddenmenu > 0 && !$_REQUEST['menuAccess'] ?
            $keySequence :
            ''
        );
        if ($_REQUEST['arch'] != 'x86_64') {
            $bzImage = $bzImage32;
            $imagefile = $init_32;
        }
        $kernel = $bzImage;
        if (self::$Host->get('kernel')) {
            $bzImage = trim(
                self::$Host->get('kernel')
            );
        }
        if (self::$Host->get('init')) {
            $imagefile = trim(
                self::$Host->get('init')
            );
        }
        $StorageGroup = $StorageNode->getStorageGroup();
        $exit = trim(
            (
                self::$Host->get($host_field_test) ?:
                self::getSetting($global_field_test)
            )
        );
        if (!$exit || !in_array($exit, array_keys(self::$_exitTypes))) {
            $exit = 'sanboot';
        }
        $initrd = $imagefile;
        if (self::$Host->isValid()) {
            self::$HookManager->processEvent(
                'BOOT_ITEM_NEW_SETTINGS',
                [
                    'Host' => &self::$Host,
                    'StorageGroup' => &$StorageGroup,
                    'StorageNode' => &$StorageNode,
                    'webserver' => &$webserver,
                    'webroot' => &$webroot,
                    'memtest' => &$memtest,
                    'memdisk' => &$memdisk,
                    'bzImage' => &$bzImage,
                    'imagefile' => &$imagefile,
                    'initrd' => &$initrd,
                    'loglevel' => &$loglevel,
                    'ramsize' => &$ramsize,
                    'keymap' => &$keymap,
                    'timeout' => &$timeout,
                    'keySequence' => &$keySequence,
                ]
            );
        }
        $kernel = $bzImage;
        $initrd = $imagefile;
        $this->_timeout = $timeout;
        $this->_hiddenmenu = ($hiddenmenu && !$_REQUEST['menuAccess']);
        $this->_bootexittype = self::$_exitTypes[$exit];
        $this->_loglevel = "loglevel=$loglevel";
        $this->_KS = self::getClass('KeySequence', $keySequence);
        $this->_booturl = self::$httpproto
            . "://{$webserver}/fog/service";
        $this->_memdisk = "kernel $memdisk initrd=$memtest";
        $this->_memtest = "initrd $memtest";
        Route::listem(
            'storagenode',
            ['ip' => [$webserver, self::resolveHostname($webserver)]]
        );
        $StorageNodes = json_decode(
            Route::getData()
        );
        if (count($StorageNodes->data ?: []) < 1) {
            Route::listem('storagenode');
            $StorageNodes = json_decode(
                Route::getData()
            );
            foreach ($StorageNodes->data as &$StorageNode) {
                $hostname = self::resolveHostname($StorageNode->ip);
                if ($hostname == $webserver
                    || $hostname == self::resolveHostname($webserver)
                ) {
                    $StorageNode = self::getClass('StorageNode', $StorageNode->id);
                    break;
                }
                $StorageNode = new StorageNode(0);
            }
            if (!$StorageNode->isValid()) {
                Route::ids(
                    'storagenode',
                    ['isMaster' => 1]
                );
                $storageNodeIDs = json_decode(Route::getData(), true);
                if (count($storageNodeIDs ?: []) < 1) {
                    Route::ids('storagenode');
                    $storageNodeIDs = json_decode(Route::getData(), true);
                }
                $StorageNode = new StorageNode(@min($storageNodeIDs));
            }
        } else {
            $first = array_shift($StorageNodes->data);
            $StorageNode = new StorageNode($first->id);
        }
        if ($StorageNode->isValid()) {
            $this->_storage = sprintf(
                'storage=%s:/%s/ storageip=%s',
                trim($StorageNode->get('ip')),
                trim($StorageNode->get('path'), '/'),
                trim($StorageNode->get('ip'))
            );
        }
        $this->_kernel = sprintf(
            'kernel %s %s initrd=%s root=/dev/ram0 rw '
            . 'ramdisk_size=%s%sweb=%s consoleblank=0%s rootfstype=ext4%s%s '
            . '%s',
            $bzImage,
            $this->_loglevel,
            basename($initrd),
            $ramsize,
            strlen($keymap) ? sprintf(' keymap=%s ', $keymap) : ' ',
            $this->_web,
            $kernelDebug ? ' debug' : ' ',
            $kernelArgs ? sprintf(' %s', $kernelArgs) : '',
            (
                self::$Host->isValid() && self::$Host->get('kernelArgs') ?
                sprintf(' %s', self::$Host->get('kernelArgs')) :
                ''
            ),
            $this->_storage
        );
        $this->_initrd = "imgfetch $imagefile";
        self::$HookManager
            ->processEvent('BOOT_MENU_ITEM');
        Route::ids(
            'pxemenuoptions',
            ['default' => 1]
        );
        $PXEMenuID = @max(json_decode(Route::getData(), true));
        $defaultMenu = new PXEMenuOptions($PXEMenuID);
        $menuname = (
            $defaultMenu->isValid() ?
            trim($defaultMenu->get('name')) :
            'fog.local'
        );
        unset($defaultMenu);
        self::_getDefaultMenu(
            $this->_timeout,
            $menuname,
            $this->_defaultChoice
        );
        $this->_ipxeLog();
        if (self::$Host->isValid() && self::$Host->get('task')->isValid()) {
            $this->getTasking();
            exit;
        }
        self::$HookManager->processEvent(
            'ALTERNATE_BOOT_CHECKS'
        );
        if (isset($_REQUEST['username'])) {
            $this->verifyCreds();
        } elseif ($_REQUEST['delconf']) {
            $this->_delHost();
        } elseif ($_REQUEST['key']) {
            $this->keyset();
        } elseif ($_REQUEST['sessname']) {
            $this->sesscheck();
        } elseif ($_REQUEST['aprvconf']) {
            $this->_approveHost();
        } elseif (!self::$Host->isValid()) {
            $this->printDefault();
        } else {
            $this->getTasking();
        }
    }
    /**
     * Sets the default menu item
     *
     * @param int    $timeout the timeout interval
     * @param string $name    the name to default to
     * @param mixed  $default the default item to set
     *
     * @return void
     */
    private static function _getDefaultMenu($timeout, $name, &$default)
    {
        $default = sprintf(
            'choose --default %s --timeout %s target && goto ${target}',
            $name,
            $timeout
        );
    }
    /**
     * Log's the current ipxe request
     *
     * @return void
     */
    private function _ipxeLog()
    {
        $filename = trim(basename($_REQUEST['filename']));
        $product = trim($_REQUEST['product']);
        $manufacturer = trim($_REQUEST['manufacturer']);
        $findWhere = [
            'file' => sprintf('%s', $filename ? $filename : ''),
            'product' => sprintf('%s', $product ? $product : ''),
            'manufacturer' => sprintf('%s', $manufacturer ? $manufacturer : ''),
            'mac' => (
                self::$Host->isValid() ?
                self::$Host->get('mac')->__toString() :
                ''
            ),
        ];
        Route::ids(
            'ipxe',
            $findWhere
        );
        $id = @max(json_decode(Route::getData(), true));
        self::getClass('iPXE', $id)
            ->set('product', $findWhere['product'])
            ->set('manufacturer', $findWhere['manufacturer'])
            ->set('mac', $findWhere['mac'])
            ->set('success', 1)
            ->set('failure', 0)
            ->set('file', $findWhere['file'])
            ->set('version', trim($_REQUEST['ipxever']))
            ->save();
    }
    /**
     * The boot chaining function
     *
     * @param bool $debug        to show debu gor not
     * @param bool $shortCircuit to force display
     *
     * @return void
     */
    private function _chainBoot($debug = false, $shortCircuit = false)
    {
        $debug = $debug;
        if (!$this->_hiddenmenu || $shortCircuit) {
            $Send['chainnohide'] = [
                'cpuid --ext 29 && set arch x86_64 || set arch i386',
                'params',
                'param mac0 ${net0/mac}',
                'param arch ${arch}',
                'param platform ${platform}',
                'param menuAccess 1',
                "param debug $debug",
                'param sysuuid ${uuid}',
                'isset ${net1/mac} && param mac1 ${net1/mac} || goto bootme',
                'isset ${net2/mac} && param mac2 ${net2/mac} || goto bootme',
                ':bootme',
                "chain -ar $this->_booturl/ipxe/boot.php##params",
            ];
        } else {
            $KSKey = (
                $this->_KS->isValid() ?
                trim($this->_KS->get('ascii')) :
                '0x1b'
            );
            $KSName = (
                $this->_KS->isValid() ?
                trim($this->_KS->get('name')) :
                'Escape'
            );
            $Send['chainhide'] = [
                'cpuid --ext 29 && set arch x86_64 || set arch i386',
                "iseq \${platform} efi && set key 0x1b || set key $KSKey",
                "iseq \${platform} efi && set keyName ESC || "
                . "set keyName $KSName",
                "prompt --key \${key} --timeout $this->_timeout "
                . "Booting... (Press \${keyName} to access the menu) && "
                . "goto menuAccess || $this->_bootexittype",
                ':menuAccess',
                'login',
                'params',
                'param mac0 ${net0/mac}',
                'param arch ${arch}',
                'param platform ${platform}',
                'param username ${username}',
                'param password ${password}',
                'param menuaccess 1',
                "param debug $debug",
                'param sysuuid ${uuid}',
                'isset ${net1/mac} && param mac1 ${net1/mac} || goto bootme',
                'isset ${net2/mac} && param mac2 ${net2/mac} || goto bootme',
                ':bootme',
                "chain -ar $this->_booturl/ipxe/boot.php##params",
            ];
        }
        $this->_parseMe($Send);
    }
    /**
     * Deletes the current host
     *
     * @return void
     */
    private function _delHost()
    {
        if (self::$Host->destroy()) {
            $Send['delsuccess'] = [
                'echo Host deleted successfully',
                'sleep 3'
            ];
        } else {
            $Send['delfail'] = [
                'echo Failed to destroy Host!',
                'sleep 3',
            ];
        }
        $this->_parseMe($Send);
        $this->_chainBoot();
    }
    /**
     * Print if this host is image ignored
     *
     * @return void
     */
    private function _printImageIgnored()
    {
        $Send['ignored'] = [
            'echo The MAC Address is set to be ignored for imaging tasks',
            'sleep 15',
        ];
        $this->_parseMe($Send);
        $this->printDefault();
    }
    /**
     * Approves a pending host
     *
     * @return void
     */
    private function _approveHost()
    {
        if (self::$Host->set('pending', null)->save()) {
            $Send['approvesuccess'] = [
                'echo Host approved successfully',
                'sleep 3'
            ];
            $shutdown = stripos(
                'shutdown=1',
                $_REQUEST['extraargs']
            );
            $isdebug = preg_match(
                '#isdebug=yes|mode=debug|mode=onlydebug#i',
                $_REQUEST['extraargs']
            );
            Route::indiv('tasktype', TaskType::INVENTORY);
            $tasktype = json_decode(Route::getData());
            self::$Host->createImagePackage(
                $tasktype,
                'Inventory',
                $shutdown,
                $isdebug,
                false,
                false,
                $_REQUEST['username']
            );
        } else {
            $Send['approvefail'] = [
                'echo Host approval failed',
                'sleep 3'
            ];
        }
        $this->_parseMe($Send);
        $this->_chainBoot();
    }
    /**
     * Prints the current tasking for the host
     *
     * @param array $kernelArgsArray the kernel args data
     *
     * @return void
     */
    private function _printTasking($kernelArgsArray)
    {
        $kernelArgs = [];
        foreach ((array)$kernelArgsArray as &$arg) {
            if (empty($arg)) {
                continue;
            }
            if (is_array($arg)) {
                if (!(isset($arg['value']) && $arg['value'])) {
                    continue;
                }
                if (!(isset($arg['active']) && $arg['active'])) {
                    continue;
                }
                $kernelArgs[] = preg_replace(
                    '#mode=debug|mode=onlydebug#i',
                    'isdebug=yes',
                    $arg['value']
                );
            } else {
                $kernelArgs[] = preg_replace(
                    '#mode=debug|mode=onlydebug#i',
                    'isdebug=yes',
                    $arg
                );
            }
            unset($arg);
        }
        $kernelArgs = array_filter($kernelArgs);
        $kernelArgs = array_unique($kernelArgs);
        $kernelArgs = array_values($kernelArgs);
        $kernelArgs = implode(' ', (array)$kernelArgs);
        $Send['task'][(
            self::$Host->isValid() ?
            self::$Host->get('task')->get('typeID') :
            1
        )] = [
            "$this->_kernel $kernelArgs",
            $this->_initrd,
            'boot',
        ];
        $this->_parseMe($Send);
    }
    /**
     * Presents the deletion confirmation screen
     *
     * @return void
     */
    public function delConf()
    {
        $Send['delconfirm'] = [
            'cpuid --ext 29 && set arch x86_64 || set arch i386',
            'prompt --key y Would you like to delete this host? (y/N): &&',
            'params',
            'param mac0 ${net0/mac}',
            'param arch ${arch}',
            'param platform ${platform}',
            'param delconf 1',
            'param sysuuid ${uuid}',
            'isset ${net1/mac} && param mac1 ${net1/mac} || goto bootme',
            'isset ${net2/mac} && param mac2 ${net2/mac} || goto bootme',
            ':bootme',
            "chain -ar $this->_booturl/ipxe/boot.php##params",
        ];
        $this->_parseMe($Send);
    }
    /**
     * Presents the approval confirmation screen
     *
     * @return void
     */
    public function aprvConf()
    {
        $Send['aprvconfirm'] = [
            'cpuid --ext 29 && set arch x86_64 || set arch i386',
            'prompt --key y Would you like to approve this host? (y/N): &&',
            'params',
            'param mac0 ${net0/mac}',
            'param arch ${arch}',
            'param platform ${platform}',
            'param aprvconf 1',
            'param sysuuid ${uuid}',
            'isset ${net1/mac} && param mac1 ${net1/mac} || goto bootme',
            'isset ${net2/mac} && param mac2 ${net2/mac} || goto bootme',
            ':bootme',
            "chain -ar $this->_booturl/ipxe/boot.php##params",
        ];
        $this->_parseMe($Send);
    }
    /**
     * Allows user to specify a product key at the ipxe menu
     *
     * @return void
     */
    public function keyreg()
    {
        $Send['keyreg'] = [
            'cpuid --ext 29 && set arch x86_64 || set arch i386',
            'echo -n Please enter the product key : ',
            'read key',
            'params',
            'param mac0 ${net0/mac}',
            'param arch ${arch}',
            'param platform ${platform}',
            'param key ${key}',
            'param sysuuid ${uuid}',
            'isset ${net1/mac} && param mac1 ${net1/mac} || goto bootme',
            'isset ${net2/mac} && param mac2 ${net2/mac} || goto bootme',
            ':bootme',
            "chain -ar $this->_booturl/ipxe/boot.php##params",
        ];
        $this->_parseMe($Send);
    }
    /**
     * Checks that a session is valid and integrates the host to that
     * tasking.
     *
     * @return void
     */
    public function sesscheck()
    {
        $findWhere = [
            'name' => trim($_REQUEST['sessname']),
            'stateID' => self::fastmerge(
                self::getQueuedStates(),
                (array)self::getProgressState()
            ),
        ];
        Route::listem(
            'multicastsession',
            $findWhere
        );
        $Sessions = json_decode(
            Route::getData()
        );
        foreach ($Sessions->data as &$MulticastSession) {
            if ($MulticastSession->sessclients < 1) {
                $MulticastSessionID = 0;
                unset($MulticastSession);
                continue;
            }
            $MulticastSessionID = $MulticastSession->id;
            unset($MulticastSession);
            break;
        }
        $MulticastSession = new MulticastSession($MulticastSessionID);
        if (!$MulticastSession->isValid()) {
            $Send['checksession'] = [
                'echo No session found with that name.',
                'clear sessname',
                'sleep 3',
                'cpuid --ext 29 && set arch x86_64 || set arch i386',
                'params',
                'param mac0 ${net0/mac}',
                'param arch ${arch}',
                'param platform ${platform}',
                'param sessionJoin 1',
                'param sysuuid ${uuid}',
                'isset ${net1/mac} && param mac1 ${net1/mac} || goto bootme',
                'isset ${net2/mac} && param mac2 ${net2/mac} || goto bootme',
                ':bootme',
                "chain -ar $this->_booturl/ipxe/boot.php##params",
            ];
            $this->_parseMe($Send);
            return;
        }
        $this->multijoin($MulticastSession->get('id'));
    }
    /**
     * Asks user what the name of the session is they want to join
     *
     * @return void
     */
    public function sessjoin()
    {
        $Send['joinsession'] = [
            'cpuid --ext 29 && set arch x86_64 || set arch i386',
            'echo -n Please enter the session name to join > ',
            'read sessname',
            'params',
            'param mac0 ${net0/mac}',
            'param arch ${arch}',
            'param platform ${platform}',
            'param sessname ${sessname}',
            'param sysuuid ${uuid}',
            'isset ${net1/mac} && param mac1 ${net1/mac} || goto bootme',
            'isset ${net2/mac} && param mac2 ${net2/mac} || goto bootme',
            ':bootme',
            "chain -ar $this->_booturl/ipxe/boot.php##params",
        ];
        $this->_parseMe($Send);
    }
    /**
     * False taskings are taskings for hosts that may not be
     * registered to the FOG Server.  This function allows actions
     * still occur
     *
     * @param mixed $mc    If the task is a multicast or not
     * @param mixed $Image The image to use for this false tasking
     *
     * @return void
     */
    public function falseTasking($mc = false, $Image = false)
    {
        $this->_kernel = str_replace(
            $this->_storage,
            '',
            $this->_kernel
        );
        $TaskType = new TaskType(1);
        if ($mc) {
            $Image = $mc->getImage();
            $TaskType = new TaskType(8);
            $shutdown = (bool)$mc->get('shutdown');
        }
        $serviceNames = [
            'FOG_DISABLE_CHKDSK',
            'FOG_KERNEL_ARGS',
            'FOG_KERNEL_DEBUG',
            'FOG_MULTICAST_RENDEZVOUS',
            'FOG_NONREG_DEVICE'
        ];
        list(
            $chkdsk,
            $kargs,
            $kdebug,
            $mcastrdv,
            $nondev
        ) = self::getSetting($serviceNames);
        if (!$shutdown) {
            $shutdown = false !== stripos(
                'shutdown=1',
                $TaskType->get('kernelArgs')
            );
        }
        if (!$shutdown && isset($_REQUEST['extraargs'])) {
            $shutdown = false !== stripos(
                'shutdown=1',
                $_REQUEST['extraargs']
            );
        }
        $StorageGroup = $Image->getStorageGroup();
        $StorageNode = $StorageGroup->getOptimalStorageNode();
        $osid = $Image->get('osID');
        $storage = escapeshellcmd(
            sprintf(
                '%s:/%s/%s',
                trim($StorageNode->get('ip')),
                trim($StorageNode->get('path'), '/'),
                ''
            )
        );
        $storageip = $StorageNode->get('ip');
        $img = escapeshellcmd($Image->get('path'));
        $imgFormat = (int)$Image->get('format');
        $imgType = $Image->getImageType()->get('type');
        $imgPartitionType = $Image->getPartitionType();
        $imgid = $Image->get('id');
        $chkdsk = $chkdsk == 1 ? 0 : 1;
        $ftp = $StorageNode->get('ip');
        $port = ($mc ? $mc->get('port') : null);
        $kernelArgsArray = [
            "mac=$mac",
            "ftp=$ftp",
            "storage=$storage",
            "storageip=$storageip",
            "osid=$osid",
            "irqpoll",
            "chkdsk=$chkdsk",
            "img=$img",
            "imgType=$imgType",
            "imgPartitionType=$imgPartitionType",
            "imgid=$imgid",
            "imgFormat=$imgFormat",
            [
                'value' => 'shutdown=1',
                'active' => $shutdown
            ],
            [
                'value' => "mcastrdv=$mcastrdv",
                'active' => !empty($mcastrdv)
            ],
            [
                'value' => "capone=1",
                'active' => !self::$Host || !self::$Host->isValid(),
            ],
            [
                'value' => "port=$port mc=yes",
                'active' => $mc,
            ],
            [
                'value' => 'debug',
                'active' => $kdebug,
            ],
            [
                'value' => 'fdrive='.$nondev,
                'active' => $nondev,
            ],
            $TaskType->get('kernelArgs'),
            $kargs
        ];
        $this->_printTasking($kernelArgsArray);
    }
    /**
     * Prints the image list for the ipxe menu
     *
     * @return void
     */
    public function printImageList()
    {
        $Send['ImageListing'] = [
            'goto MENU',
            ':MENU',
            'menu',
        ];
        $defItem = 'choose target && goto ${target}';
        /**
         * Sort a list.
         */
        $imgFind = ['isEnabled' => 1];
        if (!self::getSetting('FOG_IMAGE_LIST_MENU')) {
            if (!self::$Host->isValid()
                || !self::$Host->getImage()->isValid()
            ) {
                $imgFind = false;
            } else {
                $imgFind['id'] = self::$Host->getImage()->get('id');
            }
        }
        if ($imgFind === false) {
            $Images = false;
        } else {
            Route::listem(
                'image',
                $imgFind
            );
            $Images = json_decode(
                Route::getData()
            );
            $Images = $Images->data;
        }
        if (!$Images) {
            $Send['NoImages'] = [
                'echo Host is not valid, host has no image assigned, or'
                . ' there are no images defined on the server.',
                'sleep 3',
            ];
            $this->_parseMe($Send);
            $this->_chainBoot();
        } else {
            array_map(
                function (&$Image) use (&$Send, &$defItem) {
                    $Send['ImageListing'][] = sprintf(
                        'item %s %s (%s)',
                        $Image->path,
                        $Image->name,
                        $Image->id
                    );
                    if (!self::$Host->isValid()) {
                        return;
                    }
                    if (!self::$Host->getImage()->isValid()) {
                        return;
                    }
                    if (self::$Host->getImage()->get('id') === $Image->id) {
                        $defItem = sprintf(
                            'choose --default %s --timeout %d target && '
                            . 'goto ${target}',
                            $Image->path,
                            $this->_timeout
                        );
                    }
                    unset($Image);
                },
                (array)$Images
            );
            $Send['ImageListing'][] = 'item return Return to menu';
            $Send['ImageListing'][] = $defItem;
            array_map(
                function (&$Image) use (&$Send) {
                    $Send[sprintf(
                        'pathofimage%s',
                        $Image->name
                    )] = [
                        sprintf(
                            ':%s',
                            $Image->path
                        ),
                        sprintf(
                            'set imageID %d',
                            $Image->id
                        ),
                        'params',
                        'param mac0 ${net0/mac}',
                        'param arch ${arch}',
                        'param imageID ${imageID}',
                        'param qihost 1',
                        'param username ${username}',
                        'param password ${password}',
                        'param sysuuid ${uuid}',
                        'isset ${net1/mac} && param mac1 ${net1/mac} || goto bootme',
                        'isset ${net2/mac} && param mac2 ${net2/mac} || goto bootme',
                    ];
                    unset($Image);
                },
                (array)$Images
            );
            $Send['returnmenu'] = [
                ':return',
                'params',
                'param mac0 ${net0/mac}',
                'param arch ${arch}',
                'param sysuuid ${uuid}',
                'isset ${net1/mac} && param mac1 ${net1/mac} || goto bootme',
                'isset ${net2/mac} && param mac2 ${net2/mac} || goto bootme',
            ];
            $Send['bootmefunc'] = [
                ':bootme',
                "chain -ar $this->_booturl/ipxe/boot.php##params",
                'goto MENU',
            ];
            $this->_parseMe($Send);
        }
    }
    /**
     * Joins the host with a session
     *
     * @param int $msid the session to join
     *
     * @return void
     */
    public function multijoin($msid)
    {
        $MultiSess = new MulticastSession($msid);
        if (!$MultiSess->isValid()) {
            return;
        }
        $msImage = $MultiSess->getImage()->get('id');
        if (self::$Host->isValid() && !self::$Host->get('pending')) {
            $h_Image = 0;
            $Image = self::$Host->getImage();
            if ($Image instanceof Image) {
                $h_Image = self::$Host->getImage()->get('id');
            }
            if ($msImage != $h_Image) {
                self::$Host
                    ->set('imagename', $MultiSess->getImage())
                    ->set('imageID', $msImage);
            }
        }
        $shutdown = stripos(
            'shutdown=1',
            $_REQUEST['extraargs']
        );
        $isdebug = preg_match(
            '#isdebug=yes|mode=debug|mode=onlydebug#i',
            $_REQUEST['extraargs']
        );
        if (self::$Host->isValid() && !self::$Host->get('pending')) {
            Route::indiv('tasktype', TaskType::MULTICAST);
            $tasktype = json_decode(Route::getData());
            self::$Host->createImagePackage(
                $tasktype,
                $MultiSess->get('name'),
                $shutdown,
                $isdebug,
                -1,
                false,
                $_REQUEST['username'],
                '',
                true,
                true
            );
            $this->_chainBoot(false, true);
        } else {
            $this->falseTasking($MultiSess);
        }
    }
    /**
     * Set's the product key
     *
     * @return void
     */
    public function keyset()
    {
        if (!self::$Host->isValid()) {
            return;
        }
        self::$Host->set('productKey', $_REQUEST['key']);
        if (!self::$Host->save()) {
            return;
        }
        $Send['keychangesuccess'] = [
            'echo Successfully changed key',
            'sleep 3',
        ];
        $this->_parseMe($Send);
        $this->_chainBoot();
    }
    /**
     * Parses the information for us
     *
     * @param array $Send the data to parse
     *
     * @return void
     */
    private function _parseMe($Send)
    {
        self::$HookManager->processEvent(
            'IPXE_EDIT',
            [
                'ipxe' => &$Send,
                'Host' => &self::$Host,
                'kernel' => &$this->_kernel,
                'initrd' => &$this->_initrd,
                'booturl' => &$this->_booturl,
                'memdisk' => &$this->_memdisk,
                'memtest' => &$this->_memtest,
                'web' => &$this->_web,
                'defaultChoice' => &$this->_defaultChoice,
                'bootexittype' => &$this->_bootexittype,
                'storage' => &$this->_storage,
                'shutdown' => &$this->_shutdown,
                'path' => &$this->_path,
                'timeout' => &$this->_timeout,
                'KS' => $this->ks
            ]
        );
        if (count($Send ?: []) > 0) {
            array_walk_recursive(
                $Send,
                function (&$val, &$key) {
                    printf('%s%s', implode("\n", (array)$val), "\n");
                    unset($val, $key);
                }
            );
        }
    }
    /**
     * For advancemenu if we require login
     *
     * @return void
     */
    public function advLogin()
    {
        $Send['advancedlogin'] = [
            "chain -ar $this->_booturl/ipxe/advanced.php"
        ];
        $this->_parseMe($Send);
    }
    /**
     * Sets menus up with isdebug options
     *
     * @return void
     */
    private function _debugAccess()
    {
        $Send['debugaccess'] = [
            "$this->_kernel isdebug=yes",
            "$this->_initrd",
            "boot",
        ];
        $this->_parseMe($Send);
    }
    /**
     * Verifies credentials for us
     *
     * @return void
     */
    public function verifyCreds()
    {
        list(
            $advLogin,
            $noMenu
        ) = self::getSetting(['FOG_ADVANCED_MENU_LOGIN', 'FOG_NO_MENU']);
        if ($noMenu) {
            $this->noMenu();
        }
        $tmpUser = self::attemptLogin(
            $_REQUEST['username'],
            $_REQUEST['password']
        );
        if ($tmpUser->isValid()) {
            self::$HookManager
                ->processEvent('ALTERNATE_LOGIN_BOOT_MENU_PARAMS');
            if ($advLogin && $_REQUEST['advLog']) {
                $this->advLogin();
            }
            if ($_REQUEST['delhost']) {
                $this->delConf();
            } elseif ($_REQUEST['keyreg']) {
                $this->keyreg();
            } elseif ($_REQUEST['qihost']) {
                $this->setTasking($_REQUEST['imageID']);
            } elseif ($_REQUEST['sessionJoin']) {
                $this->sessjoin();
            } elseif ($_REQUEST['approveHost']) {
                $this->aprvConf();
            } elseif ($_REQUEST['menuaccess']) {
                unset($this->_hiddenmenu);
                $this->_chainBoot(true);
            } elseif ($_REQUEST['debugAccess']) {
                $this->_debugAccess();
            } else {
                $this->printDefault();
            }
        } else {
            $Send['invalidlogin'] = [
                "echo Invalid login!",
                "clear username",
                "clear password",
                "sleep 3"
            ];
            $this->_parseMe($Send);
            $this->_chainBoot();
        }
    }
    /**
     * Sets a tasking element as needed
     *
     * @param mixed $imgID The image id to associate
     *
     * @return void
     */
    public function setTasking($imgID = '')
    {
        $shutdown = stripos(
            'shutdown=1',
            $_REQUEST['extraargs']
        );
        $isdebug = preg_match(
            '#isdebug=yes|mode=debug|mode=onlydebug#i',
            $_REQUEST['extraargs']
        );
        if (!$imgID) {
            $this->printImageList();
            return;
        }
        if (!self::$Host->isValid()) {
            $this->falseTasking('', self::getClass('Image', $imgID));
            return;
        }
        if (self::$Host->getImage()->get('id') != $imgID) {
            self::$Host
                ->set('imageID', $imgID)
                ->set('imagename', new Image($imgID));
        }
        if (!self::$Host->getImage()->isValid()) {
            return;
        }
        try {
            Route::indiv('tasktype', TaskType::DEPLOY);
            $tasktype = json_decode(Route::getData());
            self::$Host->createImagePackage(
                $tasktype,
                'AutoRegTask',
                $shutdown,
                $isdebug,
                -1,
                false,
                $_REQUEST['username']
            );
            $this->_chainBoot(false, true);
        } catch (Exception $e) {
            $Send['fail'] = [
                '#!ipxe',
                sprintf('echo %s', $e->getMessage()),
                'sleep 3',
            ];
            $this->_parseMe($Send);
        }
    }
    /**
     * No menu definition
     *
     * @return void
     */
    public function noMenu()
    {
        $Send['nomenu'] = [
            "$this->_bootexittype"
        ];
        $this->_parseMe($Send);
        exit;
    }
    /**
     * Get's a current tasking if any
     *
     * @return void
     */
    public function getTasking()
    {
        $Task = self::$Host->get('task');
        if (!$Task->isValid() || $Task->isSnapinTasking()) {
            $this->printDefault();
        } else {
            if (self::$Host->get('mac')->isImageIgnored()) {
                $this->_printImageIgnored();
            }
            $this->_kernel = str_replace(
                $this->_storage,
                '',
                $this->_kernel
            );
            $TaskType = $Task->getTaskType();
            $imagingTasks = $TaskType->isImagingTask();
            if ($TaskType->isMulticast()) {
                Route::ids(
                    'multicastsessionassociation',
                    ['taskID' => $Task->get('id')]
                );
                $msaID = @max(json_decode(Route::getData(), true));
                $MulticastSessionAssoc = new MulticastSessionAssociation($msaID);
                $MulticastSession = $MulticastSessionAssoc->getMulticastSession();
                if ($MulticastSession && $MulticastSession->isValid()) {
                    self::$Host->set('imageID', $MulticastSession->get('image'));
                }
            }
            if ($TaskType->isInitNeededTasking()) {
                $Image = $Task->getImage();
                $StorageGroup = null;
                $StorageNode = null;
                self::$HookManager->processEvent(
                    'BOOT_TASK_NEW_SETTINGS',
                    [
                        'Host' => &self::$Host,
                        'StorageNode' => &$StorageNode,
                        'StorageGroup' => &$StorageGroup
                    ]
                );
                if (!$StorageGroup || !$StorageGroup->isValid()) {
                    $StorageGroup = $Image->getStorageGroup();
                }
                $getter = 'getOptimalStorageNode';
                if ($Task->isCapture()
                    || $TaskType->isCapture()
                ) {
                    $StorageGroup = $Image->getPrimaryStorageGroup();
                    $getter = 'getMasterStorageNode';
                }
                if ($TaskType->isMulticast()) {
                    $getter = 'getMasterStorageNode';
                }
                if (!$StorageNode || !$StorageNode->isValid()) {
                    $StorageNode = $StorageGroup->{$getter}();
                }
                if ($Task->get('storagenodeID') != $StorageNode->get('id')) {
                    $Task->set('storagenodeID', $StorageNode->get('id'));
                }
                if ($Task->get('storagegroupID') != $StorageGroup->get('id')) {
                    $Task->set('storagegroupID', $StorageGroup->get('id'));
                }
                $Task->save();
                self::$HookManager->processEvent(
                    'BOOT_TASK_NEW_SETTINGS',
                    [
                        'Host' => &self::$Host,
                        'StorageNode' => &$StorageNode,
                        'StorageGroup' => &$StorageGroup
                    ]
                );
                $osid = (int)$Image->get('osID');
                $storage = '';
                $img = '';
                $imgFormat = '';
                $imgType = '';
                $imgPartitionType = '';
                $serviceNames = [
                    'FOG_CAPTUREIGNOREPAGEHIBER',
                    'FOG_CAPTURERESIZEPCT',
                    'FOG_CHANGE_HOSTNAME_EARLY',
                    'FOG_DISABLE_CHKDSK',
                    'FOG_KERNEL_ARGS',
                    'FOG_KERNEL_DEBUG',
                    'FOG_MULTICAST_RENDEZVOUS',
                    'FOG_PIGZ_COMP',
                    'FOG_TFTP_HOST',
                    'FOG_WIPE_TIMEOUT'
                ];
                list(
                    $cappage,
                    $capresz,
                    $hosterl,
                    $chkdsk,
                    $kargs,
                    $kdebug,
                    $mcastrdv,
                    $pigz,
                    $tftp,
                    $timeout
                ) = self::getSetting($serviceNames);
                $shutdown = false !== stripos(
                    'shutdown=1',
                    $TaskType->get('kernelArgs')
                );
                if (!$shutdown && isset($_REQUEST['extraargs'])) {
                    $shutdown = false !== stripos(
                        'shutdown=1',
                        $_REQUEST['extraargs']
                    );
                }
                $globalPIGZ = $pigz;
                $PIGZ_COMP = $globalPIGZ;
                if ($StorageNode instanceof StorageNode && $StorageNode->isValid()) {
                    $ip = trim($StorageNode->get('ip'));
                    $ftp = $ip;
                }
                if ($imagingTasks) {
                    if (!($StorageNode instanceof StorageNode
                        && $StorageNode->isValid())
                    ) {
                        throw new Exception(_('No valid storage nodes found'));
                    }
                    $storage = escapeshellcmd(
                        sprintf(
                            '%s:/%s/%s',
                            $ip,
                            trim($StorageNode->get('path'), '/'),
                            (
                                $TaskType->isCapture() ?
                                'dev/' :
                                ''
                            )
                        )
                    );
                    $storageip = $ip;
                    $img = escapeshellcmd(
                        $Image->get('path')
                    );
                    $imgFormat = (int)$Image
                        ->get('format');
                    $imgType = $Image
                        ->getImageType()
                        ->get('type');
                    $imgPartitionType = $Image
                        ->getPartitionType();
                    $imgid = $Image
                        ->get('id');
                    $image_PIGZ = $Image->get('compress');
                    if (is_numeric($image_PIGZ) && $image_PIGZ > -1) {
                        $PIGZ_COMP = $image_PIGZ;
                    }
                    if (in_array($imgFormat, ['', null, 0, 1, 2, 3, 4])) {
                        if ($PIGZ_COMP > 9) {
                            $PIGZ_COMP = 9;
                        }
                    }
                } else {
                    // These setup so postinit scripts can operate.
                    if ($StorageNode instanceof StorageNode
                        && $StorageNode->isValid()
                    ) {
                        $ip = trim($StorageNode->get('ip'));
                        $ftp = $ip;
                    } else {
                        $ip = $tftp;
                        $ftp = $tftp;
                    }
                    $storage = escapeshellcmd(
                        sprintf(
                            '%s:/%s/dev/',
                            $ip,
                            trim($StorageNode->get('path'), '/')
                        )
                    );
                    $storageip = $ip;
                }
            }
            if (self::$Host->isValid()) {
                $mac = self::$Host->get('mac');
            } else {
                $mac = $_REQUEST['mac'];
            }
            $clamav = '';
            if (in_array($TaskType->get('id'), [21, 22])) {
                $clamav = sprintf(
                    '%s:%s',
                    $ip,
                    '/opt/fog/clamav'
                );
            }
            $chkdsk = $chkdsk == 1 ? 0 : 1;
            $MACs = self::$Host->getMyMacs();
            $clientMacs = array_filter(
                (array)self::parseMacList(
                    implode(
                        '|',
                        (array)$MACs
                    ),
                    false,
                    true
                )
            );
            if (self::$Host->get('useAD')) {
                $addomain = preg_replace(
                    '#\s#',
                    '+_+',
                    self::$Host->get('ADDomain')
                );
                $adou = str_replace(
                    ';',
                    '',
                    preg_replace(
                        '#\s#',
                        '+_+',
                        self::$Host->get('ADOU')
                    )
                );
                $aduser = preg_replace(
                    '#\s#',
                    '+_+',
                    self::$Host->get('ADUser')
                );
                $adpass = preg_replace(
                    '#\s#',
                    '+_+',
                    self::$Host->get('ADPass')
                );
            }
            $fdrive = self::$Host->get('kernelDevice');
            $kernelArgsArray = [
                "mac=$mac",
                "ftp=$ftp",
                "storage=$storage",
                "storageip=$storageip",
                "osid=$osid",
                "irqpoll",
                [
                    'value' => "mcastrdv=$mcastrdv",
                    'active' => !empty($mcastrdv)
                ],
                [
                    'value' => "hostname=" . self::$Host->get('name'),
                    'active' => count($clientMacs ?: []) > 0,
                ],
                [
                    'value' => "clamav=$clamav",
                    'active' => in_array($TaskType->get('id'), [21, 22]),
                ],
                [
                    'value' => "chkdsk=$chkdsk",
                    'active' => $imagingTasks,
                ],
                [
                    'value' => "img=$img",
                    'active' => $imagingTasks,
                ],
                [
                    'value' => "imgType=$imgType",
                    'active' => $imagingTasks,
                ],
                [
                    'value' => "imgPartitionType=$imgPartitionType",
                    'active' => $imagingTasks,
                ],
                [
                    'value' => "imgid=$imgid",
                    'active' => $imagingTasks,
                ],
                [
                    'value' => "imgFormat=$imgFormat",
                    'active' => $imagingTasks,
                ],
                [
                    'value' => "PIGZ_COMP=-$PIGZ_COMP",
                    'active' => $imagingTasks,
                ],
                [
                    'value' => 'shutdown=1',
                    'active' => $Task->get('shutdown') || $shutdown,
                ],
                [
                    'value' => "adon=1 addomain=\"$addomain\" "
                    . "adou=\"$adou\" aduser=\"$aduser\" "
                    . "adpass=\"$adpass\"",
                    'active' => self::$Host->get('useAD'),
                ],
                [
                    'value' => "fdrive=$fdrive",
                    'active' => self::$Host->get('kernelDevice'),
                ],
                [
                    'value' => 'hostearly=1',
                    'active' => (
                        $hosterl
                        && $imagingTasks ?
                        true :
                        false
                    ),
                ],
                [
                    'value' => sprintf(
                        'pct=%d',
                        (
                            is_numeric($capresz)
                            && $capresz >= 5
                            && $capresz < 100 ?
                            $capresz :
                            '5'
                        )
                    ),
                    'active' => $imagingTasks && $TaskType->isCapture(),
                ],
                [
                    'value' => sprintf(
                        'ignorepg=%d',
                        (
                            $cappage ?
                            1 :
                            0
                        )
                    ),
                    'active' => $imagingTasks && $TaskType->isCapture(),
                ],
                [
                    'value' => sprintf(
                        'port=%s',
                        (
                            $TaskType->isMulticast() ?
                            $MulticastSession->get('port') :
                            null
                        )
                    ),
                    'active' => $TaskType->isMulticast(),
                ],
                [
                    'value' => sprintf(
                        'winuser=%s',
                        $Task->get('passreset')
                    ),
                    'active' => $TaskType->get('id') == '11',
                ],
                [
                    'value' => 'isdebug=yes',
                    'active' => $Task->get('isDebug'),
                ],
                [
                    'value' => 'debug',
                    'active' => $kdebug,
                ],
                [
                    'value' => 'seconds='.$timeout,
                    'active' => in_array($TaskType->get('id'), range(18, 20)),
                ],
                $TaskType->get('kernelArgs'),
                $kargs,
                self::$Host->get('kernelArgs'),
            ];
            if ($Task->get('typeID') == 4) {
                $Send['memtest'] = [
                    "$this->_memdisk iso raw",
                    "$this->_memtest",
                    "boot",
                ];
                $this->_parseMe($Send);
            } else {
                $this->_printTasking($kernelArgsArray);
            }
        }
    }
    /**
     * Generates a menu item listing
     *
     * @param object $option the menu item to work with
     * @param string $desc   the description
     *
     * @return array
     */
    private function _menuItem($option, $desc)
    {
        $name = preg_replace('#[\s]+#', '_', $option->name);
        $hotkey = ' ';
        if ($option->hotkey) {
            if ($option->keysequence) {
                $hotkey = sprintf(
                    ' --key %s ',
                    $option->keysequence
                );
            }
        }
        return ["item${hotkey}${name} ${desc}"];
    }
    /**
     * The options of the menu
     *
     * @param object $option the menu item to work with
     * @param mixed  $type   the type of the menu
     *
     * @return array
     */
    private function _menuOpt($option, $type)
    {
        $name = preg_replace('#[\s]+#', '_', $option->name);
        $name = trim(":$name");
        $type = trim($type);
        $Send = [$name];
        $params = array_filter(
            array_map(
                'trim',
                explode(
                    "\n",
                    $option->params
                )
            )
        );
        if (count($params ?: [])) {
            if ($type) {
                $index = array_search('params', $params);
                if ($index !== false && is_numeric($index)) {
                    self::arrayInsertAfter(
                        $index,
                        $params,
                        'extra',
                        "param extraargs \"$type\""
                    );
                }
            }
            $params = trim(implode("\n", (array)$params));
            $params .= "\n"
                . 'param sysuuid ${uuid}';
            $Send = self::fastmerge($Send, [$params]);
        }
        switch ($option->id) {
        case 1:
            $Send = self::fastmerge(
                $Send,
                ["$this->_bootexittype || goto MENU"]
            );
            break;
        case 2:
            $Send = self::fastmerge(
                $Send,
                [
                    "$this->_memdisk iso raw",
                    $this->_memtest,
                    'boot || goto MENU'
                ]
            );
            break;
        case 11:
            $Send = self::fastmerge(
                $Send,
                [
                    "chain -ar $this->_booturl/ipxe/advanced.php || "
                    . "goto MENU"
                ]
            );
            break;
        default:
            if (!$params) {
                $Send = self::fastmerge(
                    $Send,
                    [
                        "$this->_kernel $this->_loglevel $type",
                        $this->_initrd,
                        'boot || goto MENU'
                    ]
                );
            }
        }
        return $Send;
    }
    /**
     * Print the default information for all hosts
     *
     * @return void
     */
    public function printDefault()
    {
        if (self::$Host->isValid()
            && self::getSetting('FOG_NO_MENU')
        ) {
            $this->noMenu();
        }
        if ($this->_hiddenmenu) {
            $this->_chainBoot(true);
            return;
        }

        Route::listem('pxemenuoptions');
        $Menus = json_decode(
            Route::getData()
        );
        $ipxeGrabs = [
            'FOG_ADVANCED_MENU_LOGIN',
            'FOG_IPXE_BG_FILE',
            'FOG_IPXE_HOST_CPAIRS',
            'FOG_IPXE_INVALID_HOST_COLOURS',
            'FOG_IPXE_MAIN_COLOURS',
            'FOG_IPXE_MAIN_CPAIRS',
            'FOG_IPXE_MAIN_FALLBACK_CPAIRS',
            'FOG_IPXE_VALID_HOST_COLOURS',
            'FOG_PXE_ADVANCED',
            'FOG_REGISTRATION_ENABLED',
        ];
        list(
            $AdvLogin,
            $bgfile,
            $hostCpairs,
            $hostInvalid,
            $mainColors,
            $mainCpairs,
            $mainFallback,
            $hostValid,
            $Advanced,
            $regEnabled
        ) = self::getSetting($ipxeGrabs);
        $Send['head'] = self::fastmerge(
            [
                'cpuid --ext 29 && set arch x86_64 || set arch i386',
                'goto get_console',
                ':console_set',
            ],
            explode("\n", $mainColors),
            explode("\n", $mainCpairs),
            [
                'goto MENU',
                ':alt_console'
            ],
            explode("\n", $mainFallback),
            [
                'goto MENU',
                ':get_console',
                "console --picture $this->_booturl/ipxe/$bgfile --left 100 "
                . "--right 80 && goto console_set || goto alt_console"
            ]
        );
        $showDebug = isset($_REQUEST['debug']);
        $hostRegColor = self::$Host->isValid() ? $hostValid : $hostInvalid;
        $reg_string = 'NOT registered!';
        if (self::$Host->isValid()) {
            $reg_string = (
                self::$Host->get('pending') ?
                'pending approval!' :
                'registered as ' . self::$Host->get('name') . '!'
            );
        }
        $Send['menustart'] = self::fastmerge(
            [
                ':MENU',
                'menu',
                $hostRegColor,
            ],
            explode("\n", $hostCpairs),
            [
                "item --gap Host is $reg_string",
                'item --gap -- -------------------------------------'
            ]
        );
        $RegArrayOfStuff = [
            (
                self::$Host->isValid() ?
                (
                    self::$Host->get('pending') ?
                    6 :
                    1
                ) :
                0
            ),
            2
        ];
        if (!$regEnabled) {
            $RegArrayOfStuff = array_diff($RegArrayOfStuff, [0]);
        }
        if ($showDebug) {
            $RegArrayOfStuff[] = 3;
        }
        if ($Advanced) {
            $RegArrayOfStuff[] = (
                $AdvLogin ?
                5 :
                4
            );
        }
        Route::listem(
            'pxemenuoptions',
            ['regMenu' => $RegArrayOfStuff]
        );
        $Menus = json_decode(
            Route::getData()
        );
        array_map(
            function (&$Menu) use (&$Send) {
                $desc = trim($Menu->description);
                if (!$desc) {
                    $desc = $Menu->name;
                }
                $Send['item-' . $Menu->name] = $this->_menuItem(
                    $Menu,
                    $desc
                );
                unset($Menu);
            },
            $Menus->data
        );
        $Send['default'] = [$this->_defaultChoice];
        array_map(
            function (&$Menu) use (&$Send) {
                $Send['choice-'.$Menu->name] = $this->_menuOpt(
                    $Menu,
                    trim($Menu->args)
                );
                unset($Menu);
            },
            $Menus->data
        );
        $Send['bootme'] = [
            ':bootme',
            "chain -ar $this->_booturl/ipxe/boot.php##params ||",
            'goto MENU',
            'autoboot',
        ];
        $this->_parseMe($Send);
    }
}
