import { SensorInterface, SharedData } from "@/types";
import { usePage } from "@inertiajs/react";
import { useEffect, useMemo, useState } from "react";
import SensorMarker from "./sensor-markers";
import { Layer, Point, Source } from "react-map-gl/mapbox";
import { features } from "process";
import { useEcho, useEchoPublic } from "@laravel/echo-react";

interface SensorsObject {
     [key: string]: {
          uuid: string;
          longitude: number;
          latitude: number;
          moisture: number;
     };
}

export default function Sensors() {
     const {sensors: sensorData} = usePage<SharedData>().props;
     
     const [sensors, setSensors] = useState<SensorsObject>(
          sensorData.reduce((acc: SensorsObject, sensor: SensorInterface) => {
               acc[sensor.id] = {
                    uuid: sensor.id,
                    longitude: sensor.latest_reading.longitude,
                    latitude: sensor.latest_reading.latitude,
                    moisture: sensor.latest_reading.moisture,
               };

               return acc;
          }, {})
     );

     useEchoPublic('sensors', "SensorUpdated", ({sensor}: {sensor: SensorInterface}) => {
          console.log(sensor);
          setSensors(prev => ({
               ...prev,
               [sensor.id]: {
                    uuid: sensor.id,
                    longitude: sensor.latest_reading.longitude,
                    latitude: sensor.latest_reading.latitude,
                    moisture: sensor.latest_reading.moisture,
               }
          }));
     });

     // const geojson = useMemo<any>(() => ({
     //      type: "FeatureCollection",
     //      features: Object.values(sensors).map(sensor => ({
     //           type: "Feature",
     //           geometry: {
     //                type: "Point",
     //                coordinates: [sensor.longitude, sensor.latitude],
     //           },
     //           properties: {
     //                uuid: sensor.uuid,
     //                moisture: sensor.moisture
     //           }
     //      })),
     // }), [sensors]);


     return(
          <>
               {Object.values(sensors).map((sensor) => (
                    <SensorMarker 
                         key={sensor.uuid}
                         longitude={sensor.longitude}
                         latitude={sensor.latitude}
                         moisture={sensor.moisture}
                    />
               ))}
          </>
          // <Source id="sensors" type="geojson" data={geojson}>
          //      <Layer
          //           id="sensor-points"
          //           type="circle"
          //           paint={{
          //           "circle-radius": 7,
          //           "circle-color": [
          //                "interpolate", ["linear"], ["get", "moisture"],
          //                0, "#f87171",
          //                50, "#facc15",
          //                100, "#4ade80",
          //           ],
          //                "circle-stroke-width": 1.5,
          //                "circle-stroke-color": "#fff",
          //           }}
          //      />
          // </Source>
     );
}