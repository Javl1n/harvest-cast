import { SensorInterface, SharedData } from "@/types";
import { usePage } from "@inertiajs/react";
import { useEffect } from "react";
import SensorMarker, { SensorMarkerInterface } from "./sensor-markers";
import { useEchoPublic } from "@laravel/echo-react";
import { useAtom, useSetAtom } from "jotai";
import { filteredSensorsAtom, setSensorsAtom, updateSensorAtom } from "@/atoms/sensors-atom";

export default function Sensors() {
     const {sensors: sensorData} = usePage<SharedData>().props;
     const [sensors] = useAtom(filteredSensorsAtom);
     const setSensors = useSetAtom(setSensorsAtom);
     const updateSensor = useSetAtom(updateSensorAtom);

     // Initialize sensors from server data on mount
     useEffect(() => {
          const initialSensors = sensorData.reduce((acc: Record<string, SensorMarkerInterface>, sensor: SensorInterface) => {
               acc[sensor.id] = {
                    uuid: sensor.id,
                    longitude: sensor.latest_reading.longitude,
                    latitude: sensor.latest_reading.latitude,
                    moisture: sensor.latest_reading.moisture,
               };

               return acc;
          }, {});

          setSensors(initialSensors);
     }, [sensorData, setSensors]);

     useEchoPublic('sensors', "SensorUpdated", ({sensor}: {sensor: SensorInterface}) => {
          console.log(sensor);
          updateSensor({
               uuid: sensor.id,
               longitude: sensor.latest_reading.longitude,
               latitude: sensor.latest_reading.latitude,
               moisture: sensor.latest_reading.moisture,
          });
     });

     return(
          <>
               {Object.values(sensors).map((sensor) => (
                    <SensorMarker
                         key={sensor.uuid}
                         sensor={sensor}
                    />
               ))}
          </>
     );
}