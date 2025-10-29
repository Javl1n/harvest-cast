import { CircleDot } from "lucide-react";
import { Marker } from "react-map-gl/mapbox";
import { Tooltip, TooltipContent, TooltipTrigger } from "../ui/tooltip";
import { router } from "@inertiajs/react";
import calendar from "@/routes/calendar";

export interface SensorMarkerInterface {
     uuid: string;
     longitude: number;
     latitude: number;
     moisture: number;
     crop?: string;
};


export default function SensorMarker({sensor}: {sensor: SensorMarkerInterface})
{
     const handleMarkerClick = () => {
          router.visit(calendar.show(sensor.uuid).url);
     };

     return (
          <Marker
               onClick={handleMarkerClick}
               longitude={sensor.longitude}
               latitude={sensor.latitude}
               anchor="center"
          >
               <Tooltip>
                    <TooltipTrigger asChild>
                         <CircleDot className="size-10 stroke-3 transition cursor-pointer" style={{
                              color: `hsl(${(sensor.moisture / 100) * 120}, 100%, 50%)`
                         }} />
                    </TooltipTrigger>
                    <TooltipContent>
                         {sensor.crop && <div>Crop: {sensor.crop}</div>}
                         <div>Longitude {sensor.longitude}</div>
                         <div>Latitude: {sensor.latitude}</div>
                         <div>Moisture: {sensor.moisture}</div>
                    </TooltipContent>
               </Tooltip>
          </Marker>
     );
}