var UnitTrans = function () {
    'use strict';

    var timeSeries = [['s', 60], ['m', 60], ['h', 24], ['d', 10000]];
    var sizeSeries = [['', 1024], ['k', 1024], ['M', 1024], ['G', 1024], ['T', 1024], ['P', 10000]];

    function UnitTrans() {
    }

    function transData(d, series) {
        var str = '';
        for (var i in series) {
            var unit = series[i][0];
            var scale = series[i][1];
            if (d < scale) {
                str = d + ' ' + unit + ' ' + str;
                break;
            }
            var r = d % scale;
            if (str != '' || r != 0) str = r + ' ' + unit + ' ' + str;
            d = (d - r) / scale;
        }
        return str;
    }

    function transData1(d, series) {
        var str = '';
        for (var i in series) {
            var unit = series[i][0];
            var scale = series[i][1];
            if (d < scale) {
                str = Math.round(d * 100) / 100 + ' ' + unit;
                break;
            }
            d = d / scale;
        }
        return str;
    };

    function getCoef(series) {
        var seriesCoef = {};
        for (var i in series) {
            if (i > 0) {
                seriesCoef[series[i][0]] = seriesCoef[series[i - 1][0]] * series[i - 1][1];
            } else {
                seriesCoef[series[i][0]] = 1;
            }
        }
        return seriesCoef;
    }

    return {
        timeStr: function (d) {
            return transData(d, timeSeries);
        },
        sizeStr: function (d) {
            return transData1(d, sizeSeries) + 'iB';
        },
        strTime: function (str) {
            var seriesCoef = getCoef(timeSeries);
            var d = 0;
            var matches;
            var reg = /(\d+)\s*([dhms])/g;
            while ((matches = reg.exec(str)) != null) {
                d += parseInt(matches[1]) * seriesCoef[matches[2]];
            }
            return d;
        },
        strSize: function (str) {
            var seriesCoef = getCoef(sizeSeries);
            var d = 0;
            var matches;
            var reg = /(\d+(?:\.\d+)?)\s*([kMGTP]?)/;
            if ((matches = reg.exec(str)) != null) {
                d += parseFloat(matches[1]) * seriesCoef[matches[2]];
            }
            return Math.round(d);
        },
    };
}();
