<?php
if (! isset($can) || ! is_callable($can)) {
    $can = function ($code) {
        return false;
    };
    }
    if (! isset($hasRole) || ! is_callable($hasRole)) {
    $hasRole = function ($ids) {
        return false;
    };
    }
    if (! isset($allowedPermissionCodes)) {
    $allowedPermissionCodes = [];
    }
    if (! isset($usaMenuBi)) {
    $usaMenuBi = false;
    }
   
    if (! isset($homeActivo)) {
    $homeActivo = false;
    }
    
    $homePages = [
    'home',
    'dashboard',
    'sales',
    'partssalesperformance',
    'unitsoperation',
    'informationstatus',
    'indexreport',
    'partssalesbychannel',
    'workshopreport',
    'inventoryreport',
    'rankingreport',
    'fillratebo',
    ];

    $homeActivo = isset($loc) && in_array($loc, $homePages, true);
   
?>

<style>
    .sidebar-nav .home-submenu {
        list-style: none;
        margin: 6px 0 0;
        padding: 0 0 0 36px;
    }

    .sidebar-nav .home-toggle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        
    }

    .sidebar-nav .submenu-arrow{
        opacity: .8;
        transition: transform .2s ease;
        margin-left: 8px;
        margin-right: 20px;
        width: auto;
        display: flex;
        justify-content: flex-start;
     }

    .sidebar-nav .submenu-arrow i{
        margin-left: -6px;
    }
    .sidebar-nav .home-toggle[aria-expanded="true"] .submenu-arrow {
        transform: rotate(180deg);
    }

    .sidebar-nav .home-submenu li {
        margin-bottom: 2px;
    }

    .sidebar-nav .home-submenu a {
        display: block;
        padding: 6px 0;
        color: inherit;
        opacity: .85;
        font-size: 12px;
    }

    .sidebar-nav .home-submenu li.active a {
        opacity: 1;
        font-weight: 700;
    }
</style>
<!-- main notification links are placed inside of .sidebar-nav -->
<ul class="sidebar-nav">

    <!-- =========================
         HOME OTS (PRIORIDAD #1)
    ========================== -->

 <?php if ($can('overview')) {?>

<li class="<?php if (isset($loc) && $loc == "overview") {?> active <?php }?>">

    <a href="../admin/overview.php">

        <span class="icon">
            <i class="fa fa-home"></i>
        </span>

        Home-OTS

    </a>

</li>

<?php }?>


    <!-- =========================
         HOME KPI (PRIORIDAD #2)
    ========================== -->

    <?php if ($usaMenuBi) {?>

        <li class="<?php echo $homeActivo ? 'active' : '' ?>">

            <a class="home-toggle"
               href="#homeSubmenuRole56"
               role="button"
               aria-expanded="<?php echo $homeActivo ? 'true' : 'false' ?>"
               aria-controls="homeSubmenuRole56">

                <span>

                    <span class="icon">
                        <i class="fa fa-home"></i>
                    </span>

                    Home-KPI

                </span>

                <span class="submenu-arrow">
                    <i class="fa fa-angle-down"></i>
                </span>

            </a>

            <ul id="homeSubmenuRole56"
                class="home-submenu collapse<?php echo $homeActivo ? ' show' : '' ?>">

                <?php if ($can('dashboard')) {?>
                    <li class="<?php if (isset($loc) && $loc == "dashboard") {?> active <?php }?>">
                        <a href="../admin/dashboard.php">Dashboard</a>
                    </li>
                <?php }?>

                <?php if ($can('sales')) {?>
                    <li class="<?php if (isset($loc) && $loc == "sales") {?> active <?php }?>">
                        <a href="../admin/dashboardSales.php">Dashboard Sales</a>
                    </li>
                <?php }?>

                <?php if ($can('partssalesperformance')) {?>
                    <li class="<?php if (isset($loc) && $loc == "partssalesperformance") {?> active <?php }?>">
                        <a href="../admin/partSalesPerformance.php">Part Sales Performance</a>
                    </li>
                <?php }?>

                <?php if ($can('unitsoperation')) {?>
                    <li class="<?php if (isset($loc) && $loc == "unitsoperation") {?> active <?php }?>">
                        <a href="../admin/unitsoperation.php">Units in Operation</a>
                    </li>
                <?php }?>

                <?php if ($can('informationstatus')) {?>
                    <li class="<?php if (isset($loc) && $loc == "informationstatus") {?> active <?php }?>">
                        <a href="../admin/informationstatus.php">Information Status Report</a>
                    </li>
                <?php }?>

                <?php if ($can('indexreport')) {?>
                    <li class="<?php if (isset($loc) && $loc == "indexreport") {?> active <?php }?>">
                        <a href="../admin/indexReport.php">Index Report</a>
                    </li>
                <?php }?>

                <?php if ($can('partssalesbychannel')) {?>
                    <li class="<?php if (isset($loc) && $loc == "partssalesbychannel") {?> active <?php }?>">
                        <a href="../admin/partsSalesByChannel.php">Parts Sales by Channel</a>
                    </li>
                <?php }?>

                <?php if ($can('workshopreport')) {?>
                    <li class="<?php if (isset($loc) && $loc == "workshopreport") {?> active <?php }?>">
                        <a href="../admin/workshopReport.php">Workshop Report</a>
                    </li>
                <?php }?>

                <?php if ($can('inventoryreport')) {?>
                    <li class="<?php if (isset($loc) && $loc == "inventoryreport") {?> active <?php }?>">
                        <a href="../admin/inventoryReport.php">Inventory Report</a>
                    </li>
                <?php }?>

                <?php if ($can('rankingreport')) {?>
                    <li class="<?php if (isset($loc) && $loc == "rankingreport") {?> active <?php }?>">
                        <a href="../admin/rankingReport.php">Ranking Report</a>
                    </li>
                <?php }?>

                <?php if ($can('fillratebo')) {?>
                    <li class="<?php if (isset($loc) && $loc == "fillratebo") {?> active <?php }?>">
                        <a href="../admin/fillRateBO.php">Fill Rate &amp; B/O</a>
                    </li>
                <?php }?>

            </ul>

        </li>

    <?php }?>


    <!-- =========================
         MENÚ OTS
    ========================== -->

    <?php if ($can('new')) {?>
        <li class="<?php if (isset($loc) && $loc == "new") {?> active <?php }?>">
            <a href="../admin/new.php">
                <span class="icon">
                    <i class="fa fa-plus-square"></i>
                </span>
                New Order
            </a>
        </li>
    <?php }?>

    <?php if ($can('exist')) {?>
        <li class="<?php if (isset($loc) && $loc == "exist") {?> active <?php }?>">
            <a href="../admin/exist.php">
                <span class="icon">
                    <i class="fa fa-puzzle-piece"></i>
                </span>

                Existing Order

                <?php if (isset($mensajesPend) && count($mensajesPend) > 0) {?>
                    <span class="badge badge-info">
                        <?php echo(count($mensajesPend)); ?>
                    </span>
                <?php }?>

            </a>
        </li>
    <?php }?>

    <?php if ($can('list')) {?>
        <li class="<?php if (isset($loc) && $loc == "list") {?> active <?php }?>">
            <a href="../admin/listitems.php">
                <span class="icon">
                    <i class="fa fa-reorder"></i>
                </span>
                Price List
            </a>
        </li>
    <?php }?>

    <?php if ($can('calendar')) {?>
        <li class="<?php if (isset($loc) && $loc == "calendar") {?> active <?php }?>">
            <a href="../admin/calendar.php">
                <span class="icon">
                    <i class="fa fa-calendar"></i>
                </span>
                Ordering Calendar
            </a>
        </li>
    <?php }?>

    <?php if ($can('master')) {?>
        <li class="<?php if (isset($loc) && $loc == "master") {?> active <?php }?>">
            <a href="../admin/master.php">
                <span class="icon">
                    <i class="fa fa-file-excel-o"></i>
                </span>
                Master Report
            </a>
        </li>
    <?php }?>


    <!-- =========================
         OTROS MENÚS
    ========================== -->

    <?php if ($can('dealer')) {?>
        <li class="<?php if (isset($loc) && $loc == "dealer") {?> active <?php }?>">
            <a href="../admin/dealers.php">
                <span class="icon">
                    <i class="fa fa-building-o"></i>
                </span>
                Dealer information
            </a>
        </li>
    <?php }?>

    <?php if ($can('holiday')) {?>
        <li class="<?php if (isset($loc) && $loc == "holiday") {?> active <?php }?>">
            <a href="../admin/holidays.php">
                <span class="icon">
                    <i class="fa fa-calendar-times-o"></i>
                </span>
                Holidays
            </a>
        </li>
    <?php }?>

    <?php if ($can('dealertarget')) {?>
        <li class="<?php if (isset($loc) && $loc == "dealertarget") {?> active <?php }?>">
            <a href="../admin/TargetxDealer.php">
                <span class="icon">
                    <i class="fa fa-bullseye"></i>
                </span>
                Target x Dealer
            </a>
        </li>
    <?php }?>

    <?php if ($can('kpidata')) {?>
        <li class="<?php if (isset($loc) && $loc == "kpidata") {?> active <?php }?>">
            <a href="../admin/kpidata.php">
                <span class="icon">
                    <i class="fa fa-line-chart"></i>
                </span>
                KPI
            </a>
        </li>
    <?php }?>

    <?php if ($can('user')) {?>
        <li class="<?php if (isset($loc) && $loc == "user") {?> active <?php }?>">
            <a href="../admin/users.php">
                <span class="icon">
                    <i class="fa fa-users"></i>
                </span>

                Users

                <?php if ($hasRole(1) && isset($inactivosUs['cant']) && $inactivosUs['cant'] > 0) {?>
                    <span class="badge badge-info">
                        <?php echo($inactivosUs['cant']); ?>
                    </span>
                <?php }?>

            </a>
        </li>
    <?php }?>

    <?php if ($hasRole(1)) {?>
        <li class="<?php if (isset($loc) && $loc == "role_permissions") {?> active <?php }?>">
            <a href="../admin/role_permissions.php">
                <span class="icon">
                    <i class="fa fa-lock"></i>
                </span>
                Role Permissions
            </a>
        </li>
    <?php }?>

</ul>
<!-- every .sidebar-nav may have a title -->
<script>

document.addEventListener('DOMContentLoaded', function () {

    var homeToggle = document.querySelector('.home-toggle');

    var submenu = document.getElementById('homeSubmenuRole56');

    if (!homeToggle || !submenu) {
        return;
    }

    homeToggle.addEventListener('click', function (e) {

        e.preventDefault();

        var isOpen = submenu.classList.contains('show');

        if (isOpen) {

            submenu.classList.remove('show');

            this.setAttribute('aria-expanded', 'false');

        } else {

            submenu.classList.add('show');

            this.setAttribute('aria-expanded', 'true');

        }

    });

});

</script>

