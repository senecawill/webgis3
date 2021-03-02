/**
 *
 *  Seneca WebGIS 3 app.js
 *
 */

var app = new gm3.Application({
    mapserver_url: CONFIG.mapserver_url,
    mapfile_root: CONFIG.mapfile_root,

    map: {
        scaleLine: {
            enabled: true,
            units: 'imperial'
        }
    }

});

app.uiUpdate = function(ui) {
    // when the UI hint is set for the service manager
    //  show the service manager tab.
    if(ui.hint == 'service-manager' || ui.hint == 'service-start') {
        showTabByName('service-tab');
        app.clearHint();
    }
}

// change to mapbook.php to create user-specific layer lists etc.

app.loadMapbook({url: './scripts/mapbook.php'}).then(function() {
    // set the default view.
    app.setView({
        center: [ -8950659, 4738816 ],
        zoom: 8
    });

    // establish some state trackers
    var tracker = new gm3.trackers.LocalStorageTracker(app.store);
    var hash_tracker = new gm3.trackers.HashTracker(app.store);

    tracker.restore();
    hash_tracker.restore();

	// set to google projection.
    app.addProjection({
        ref: 'EPSG:3857',
        def: '+proj=merc +lon_0=0 +k=1 +x_0=0 +y_0=0 +a=6378137 +b=6378137 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs'
    });

    app.registerService('identify', IdentifyService);

    app.registerService('search-wells', SearchService, {
        fields: [
            {type: 'text', label: 'API', name: 'api'},
            {type: 'text', label: 'Operator', name: 'operator'},
            {type: 'text', label: 'Farm Name', name: 'farmname'},
            {type: 'text', label: 'Well Number', name: 'wellnumber'},
            {type: 'text', label: 'County', name: 'county'},
            {type: 'text', label: 'District', name: 'district'},
            {type: 'text', label: 'Quadrangle', name: 'quad'}
        ],
        searchLayers: ['WV_Wells_WFS/WebGIS:WV_Wells'],
        validateFieldValues: function (fields) {
            let nonEmpty = 0;
            const validateFieldValuesResult = {
                valid: true,
                message: null
            };

            if (fields['api'] !== undefined && fields['api'] !== '') {
                    nonEmpty++;
            }
            if (fields['operator'] !== undefined && fields['operator'] !== '') {
                    nonEmpty++;
            }
            if (fields['farmname'] !== undefined && fields['farmname'] !== '') {
                nonEmpty++;
            }
            if (fields['wellnumber'] !== undefined && fields['wellnumber'] !== '') {
                nonEmpty++;
            }

            if (fields['county'] !== undefined && fields['county'] !== '') {
                    nonEmpty++;
            }
            if (fields['district'] !== undefined && fields['district'] !== '') {
                    nonEmpty++;
            }
            if (fields['quad'] !== undefined && fields['quad'] !== '') {
                    nonEmpty++;
            }

            if (nonEmpty === 0) {
                validateFieldValuesResult.valid = false;
                validateFieldValuesResult.message = 'Please complete at least one field.'
            }
            return validateFieldValuesResult;
        }
    });

    
	// search service for the firestations layer
    app.registerService('search-firestations', SearchService, {
        searchLayers: ['firestations/fire_stations'],
        fields: [
            {type: 'text', label: 'Station city', name: 'Dak_GIS__4'}
        ]
    });
    app.registerService('select', SelectService, {
        // set the default layer
        defaultLayer: 'vector-parcels/parcels',
    });

    // This uses the OpenStreetMap Nominatim geocoder,
    // there is also a BingGeocoder service, but requires
    // signing up for Bing and getting an appropriate usage key.
    app.registerService('geocode', OSMGeocoder, {});
    app.registerAction('findme', FindMeAction);

    app.registerAction('fullextent', ZoomToAction, {
        extent: [-8950659,4738816]
    });

    app.add(gm3.components.Catalog, 'catalog');
    app.add(gm3.components.Favorites, 'favorites');
    app.add(gm3.components.VisibleLayers, 'visible-layers');
    app.add(gm3.components.Toolbar, 'toolbar');
    app.add(gm3.components.Grid, 'results-grid');
    app.add(gm3.components.Version, 'version');

    var point_projections = [
        {
            label: 'X,Y',
            ref: 'xy'
        },
        {
            label: 'USNG',
            ref: 'usng'
        },
        {
            label: 'Lat,Lon',
            ref: 'latlon'
        }
    ];

    app.add(gm3.components.CoordinateDisplay, 'coordinate-display', {
        projections:  point_projections
    });
    app.add(gm3.components.ServiceManager, 'service-tab', {
        services: true,
        measureToolOptions: {
            pointProjections: point_projections,
            initialUnits: 'mi'
        }
    });

    app.add(gm3.components.JumpToExtent, 'jump-to-extent', {
        locations:  [
            {
                label: 'Parcel Boundaries',
                extent: [-10384071.6,5538681.6,-10356783.6,5563600.1]
            },
            {
                label: 'Dakota County',
                extent: [-10381354,5545268,-10328765,5608252]
            },
            {
                label: 'Minnesota',
                extent: [-10807000,5440700,-9985100,6345700]
            }
        ]
    });

    app.add(gm3.components.Map, 'map', {});

    var print_preview = app.add(gm3.components.PrintModal, 'print-preview', {});
    app.registerAction('print', function() {
        this.run = function() {
           print_preview.setState({open: true});
        }
    }, {});

    app.registerAction('reload', function() {
        this.run = function() {
            var reload_msg = 'Are you sure you want to start over? All unsaved work will be lost.';
            app.confirm('reload-okay', reload_msg, function(response) {
                if(response === 'confirm') {
                    document.location.hash = '';
                    document.location.reload();
                }
            });
        }
    });


    tracker.startTracking();
    hash_tracker.startTracking();
    //changed by Will
    showTab('catalog');
});

/*
* The MIT License (MIT)
*
* Copyright (c) 2016-2017 Dan "Ducky" Little
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in all
* copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
* SOFTWARE.
*/
