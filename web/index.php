<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BXL Window</title>
    <?php $json = realpath(__DIR__ . '/../output/images-meta.json'); ?>
    <script>window.images = <?php echo file_get_contents($json) ?>;</script>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: #000;
            color: #fff;
        }
        #viewer {
            height: 100%;
            background-position: center;
            background-size: contain;
            background-repeat: no-repeat;
        }
        #legend {
            position: absolute; right: 0; bottom: 9px; z-index: 999;
            text-align: right;
        }
        #legend span {
            display: inline-block;
            margin-right: 9px;
        }
        #loading {
            display: none;
        }
        .loading #loading {
            display: inline-block;
        }
        #time {
            text-transform: uppercase;
            font: 11px/0 "Franklin Gothic Medium", "Franklin Gothic", "ITC Franklin Gothic", Arial, sans-serif;
            letter-spacing: .025em;
        }
    </style>
</head>
<body>
<div id="viewer"></div>
<div id="legend">
    <span id="loading" style="display:none">loading...</span>
    <span id="time"></span>
</div>
<script src="script.js"></script>
</body>
</html>
