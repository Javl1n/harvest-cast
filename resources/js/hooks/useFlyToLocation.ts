import { useEffect } from 'react';
import { useMap } from 'react-map-gl/mapbox';

interface FlyToOptions {
    zoom?: number;
    duration?: number;
    offset?: [number, number];
}

export function useFlyToLocation(
    latitude?: number,
    longitude?: number,
    options: FlyToOptions = {}
): void {
    const { appMap } = useMap();
    const { zoom = 14, duration = 1500, offset = [0, 0] } = options;

    useEffect(() => {
        if (latitude !== undefined && longitude !== undefined && appMap) {
            appMap.flyTo({
                center: [longitude, latitude],
                zoom,
                duration,
                offset,
            });
        }
    }, [latitude, longitude, appMap, zoom, duration, offset]);
}
