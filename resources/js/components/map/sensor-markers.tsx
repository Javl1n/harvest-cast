import { CircleDot, CircleDotDashed, MapPin } from "lucide-react";
import { Marker } from "react-map-gl/mapbox";

export default function SensorMarker({longitude, latitude, moisture}: {longitude: number, latitude: number, moisture: number}) 
{
     return (
          <Marker
               longitude={longitude}
               latitude={latitude}
               anchor="center"
          >
               <CircleDot className="size-10 stroke-3 transition" style={{
                    color: `hsl(${(moisture / 100) * 120}, 100%, 50%)`
               }} />
          </Marker>
     );
}