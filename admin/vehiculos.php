<?php
$loc = "vehiculos";
include_once '../admin/src/seguridad.php';
?>
<!DOCTYPE html>
<html>

<head>
    <?php require_once("src/header.php"); ?>
</head>

<body class="">
    <?php require_once("src/navbar.php"); ?>
<!--
  Main sidebar seen on the left. may be static or collapsing depending on selected state.

    * Collapsing - navigation automatically collapse when mouse leaves it and expand when enters.
    * Static - stays always open.
-->
<nav id="sidebar" class="sidebar" role="navigation">
    <!-- need this .js class to initiate slimscroll -->
    <div class="js-sidebar-content">
        <header class="logo d-none d-md-block">
            <a href="../admin/home"><span class="fw-thin">CAYL</span> <b>SAL</b></a>
        </header>
        <!-- seems like lots of recent admin template have this feature of user info in the sidebar.
             looks good, so adding it and enhancing with notifications -->
        <?php require_once("src/sidebar.php"); ?>
    </div>
</nav>

    <div class="content-wrap">
        <!-- main page content. the place to put widgets in. usually consists of .row > .col-lg-* > .widget.  -->
        <main id="content" class="content" role="main">
            <!-- Page content -->
<ol class="breadcrumb">
    <li class="breadcrumb-item">YOU ARE HERE</li>
    <li class="breadcrumb-item active">Tables Dynamic</li>
</ol>
<h2 class="page-title">Dynamic - <span class="fw-semi-bold">Tables</span></h2>

<section class="widget">
    <header>
        <h4>Table <span class="fw-semi-bold">Styles</span></h4>
        <div class="widget-controls">
            <a data-widgster="expand" title="Expand" href="#"><i class="la la-angle-up"></i></a>
            <a data-widgster="collapse" title="Collapse" href="#"><i class="la la-angle-down"></i></a>
            <a data-widgster="close" title="Close" href="#"><i class="la la-remove"></i></a>
        </div>
    </header>
    <div class="widget-body overflow-auto">
        <p>
            Column sorting, live search, pagination. Built with
            <a href="http://www.datatables.net/" target="_blank">jQuery DataTables</a>
        </p>
        <div class="mt">
            <table id="datatable-table" class="table table-striped table-hover overflow-auto">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Name</th>
                        <th class="no-sort d-none d-md-table-cell">Info</th>
                        <th class="d-none d-md-table-cell">Description</th>
                        <th class="d-none d-md-table-cell">Date</th>
                        <th class="no-sort">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><span class="fw-semi-bold">Algerd</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; JPEG
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 200x150
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Palo Alto</a></td>
                        <td class="d-none d-md-table-cell">June 27, 2013</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-success" style="width: 29%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td><span class="fw-semi-bold">Vitaut</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; PNG
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 6433x4522
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Vilnia</a></td>
                        <td class="d-none d-md-table-cell">January 1, 1442</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-danger" style="width: 19%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td><span class="fw-semi-bold">Honar</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; AVI
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 1440x980
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Berlin</a></td>
                        <td class="d-none d-md-table-cell">August 6, 2013</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar bg-primary" style="width: 49%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td><span class="fw-semi-bold">Jack</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; PNG
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 12x43
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">San Francisco</a></td>
                        <td class="d-none d-md-table-cell">August 19, 2013</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar bg-primary" style="width: 69%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>5</td>
                        <td><span class="fw-semi-bold">Leon</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; MP4
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 800x480
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Brasilia</a></td>
                        <td class="d-none d-md-table-cell">October 1, 2013</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar bg-primary" style="width: 9%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>6</td>
                        <td><span class="fw-semi-bold">Max</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; TXT
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; -
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Helsinki</a></td>
                        <td class="d-none d-md-table-cell">October 29, 2013</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-warning" style="width: 38%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>7</td>
                        <td><span class="fw-semi-bold">Pol</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; MOV
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 640x480
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Radashkovichi</a></td>
                        <td class="d-none d-md-table-cell">November 11, 2013</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-danger" style="width: 83%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>8</td>
                        <td><span class="fw-semi-bold">Chrishna</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; DOC
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; -
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Mumbai</a></td>
                        <td class="d-none d-md-table-cell">December 2, 2013</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-info" style="width: 40%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>9</td>
                        <td><span class="fw-semi-bold">Leslie</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; AVI
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 4820x2140
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Singapore</a></td>
                        <td class="d-none d-md-table-cell">December 6, 2013</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-warning" style="width: 18%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>10</td>
                        <td><span class="fw-semi-bold">David</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; XML
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; -
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Portland</a></td>
                        <td class="d-none d-md-table-cell">December 13, 2013</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-danger" style="width: 54%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>11</td>
                        <td><span class="fw-semi-bold">Andrej</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; VOB
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 6433x4522
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Minsk</a></td>
                        <td class="d-none d-md-table-cell">December 14, 2013</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar bg-primary" style="width: 25%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>12</td>
                        <td><span class="fw-semi-bold">Julia</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; JPEG
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 40x40
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Hrodna</a></td>
                        <td class="d-none d-md-table-cell">July 9, 2012</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-warning" style="width: 50%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>13</td>
                        <td><span class="fw-semi-bold">Ihnat</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; JAVA
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; -
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Los Angeles</a></td>
                        <td class="d-none d-md-table-cell">August 2, 2012</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-success" style="width: 8%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>14</td>
                        <td><span class="fw-semi-bold">Abraham</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; DOCX
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; -
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Panama</a></td>
                        <td class="d-none d-md-table-cell">September 3, 2012</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-danger" style="width: 80%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>15</td>
                        <td><span class="fw-semi-bold">Tomas</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; JPEG
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 1800x1420
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Amsterdam</a></td>
                        <td class="d-none d-md-table-cell">November 13, 2012</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-danger" style="width: 10%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>16</td>
                        <td><span class="fw-semi-bold">Scott</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; PNG
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 240x460
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Sluck</a></td>
                        <td class="d-none d-md-table-cell">December 5, 2012</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar bg-primary" style="width: 93%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>17</td>
                        <td><span class="fw-semi-bold">Pham</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; MAIL
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; -
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Tokyo</a></td>
                        <td class="d-none d-md-table-cell">December 8, 2012</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-danger" style="width: 44%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>18</td>
                        <td><span class="fw-semi-bold">Peter</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; PNG
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 8320x6400
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Cape Town</a></td>
                        <td class="d-none d-md-table-cell">December 29, 2012</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar  bg-danger" style="width: 5%"></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>19</td>
                        <td><span class="fw-semi-bold">Uladz'</span></td>
                        <td class="d-none d-md-table-cell">
                            <small>
                                <span class="fw-semi-bold">Type:</span>
                                &nbsp; JPEG
                            </small>
                            <br>
                            <small>
                                <span class="fw-semi-bold">Dimensions:</span>
                                &nbsp; 2200x1600
                            </small>
                        </td>
                        <td class="d-none d-md-table-cell"><a href="#">Mahileu</a></td>
                        <td class="d-none d-md-table-cell">December 7, 2013</td>
                        <td class="width-150">
                            <div class="progress  ">
                                <div class="progress-bar bg-primary" style="width: 0%"></div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

            <footer class="content-footer hidden-print">
                <?php require_once("src/footer.php"); ?>
            </footer>
        </main>
    </div>
    <?php require_once("src/scripts.php"); ?>
</body>

</html>