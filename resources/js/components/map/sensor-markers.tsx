import { CircleDot, CircleDotDashed, MapPin } from "lucide-react";
import { Marker } from "react-map-gl/mapbox";
import { Tooltip, TooltipContent, TooltipTrigger } from "../ui/tooltip";
import { useAtomValue } from 'jotai';
import { SensorsObject } from "./sensors";
import { markerSensorActionAtom } from "@/atoms/map-atoms";

export interface SensorMarkerInterface {
     uuid: string;
     longitude: number;
     latitude: number;
     moisture: number;
};


export default function SensorMarker({sensor}: {sensor: SensorMarkerInterface}) 
{
     const markerAction = useAtomValue(markerSensorActionAtom);

     return (
          <Marker
               onClick={() => markerAction(sensor.uuid)}
               longitude={sensor.longitude}
               latitude={sensor.latitude}
               anchor="center"
          > 
               <Tooltip>
                    <TooltipTrigger asChild>
                         <CircleDot className="size-10 stroke-3 transition" style={{
                              color: `hsl(${(sensor.moisture / 100) * 120}, 100%, 50%)`
                         }} />
                    </TooltipTrigger>
                    <TooltipContent>
                         <div>Longitude {sensor.longitude}</div>
                         <div>Latitude: {sensor.latitude}</div>
                         <div>Moisture: {sensor.moisture}</div>
                    </TooltipContent>
               </Tooltip>
          </Marker>
     );
}