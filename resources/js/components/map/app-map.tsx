import Map from 'react-map-gl/mapbox';
import 'mapbox-gl/dist/mapbox-gl.css';
import { useAppearance } from '@/hooks/use-appearance';

export default function AppMap() {
     const { appearance, updateAppearance } = useAppearance();

     const style = {
          'light' : 'satellite-v9',
          // 'light' : 'streets-v9',
          'dark' : 'satellite-v9',
          'system': 'satellite-v9'
     }

     return (
          <Map
               mapboxAccessToken={import.meta.env.VITE_MAPBOX_ACCESS_TOKEN}
               initialViewState={{
                    longitude: 125.077261,
                    latitude: 6.219394,
                    zoom: 14
               }}
               style={{
                    width: "100%", 
                    height: "100%"
               }}
               mapStyle={`mapbox://styles/mapbox/${style[appearance]}`}
          />
     )
}

// 6.219394, 125.077261