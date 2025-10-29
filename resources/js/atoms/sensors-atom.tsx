import { atom } from "jotai";
import { SensorMarkerInterface } from "@/components/map/sensor-markers";

export interface SensorsObject {
     [key: string]: SensorMarkerInterface;
}

// Atom to store the sensors object
export const sensorsAtom = atom<SensorsObject>({});

// Atom to store the filter - null means show all sensors, string means show only that sensor UUID
export const sensorFilterAtom = atom<string | null>(null);

// Derived atom to get filtered sensors
export const filteredSensorsAtom = atom((get) => {
    const sensors = get(sensorsAtom);
    const filter = get(sensorFilterAtom);

    if (!filter) {
        return sensors;
    }

    // Only return the filtered sensor
    if (sensors[filter]) {
        return { [filter]: sensors[filter] };
    }

    return {};
});

// Action atom to update a single sensor
export const updateSensorAtom = atom(
    null,
    (get, set, sensor: SensorMarkerInterface) => {
        const sensors = get(sensorsAtom);
        set(sensorsAtom, {
            ...sensors,
            [sensor.uuid]: sensor
        });
    }
);

// Action atom to set all sensors at once
export const setSensorsAtom = atom(
    null,
    (_get, set, sensors: SensorsObject) => {
        set(sensorsAtom, sensors);
    }
);

// Action atom to filter to a specific sensor
export const filterToSensorAtom = atom(
    null,
    (_get, set, uuid: string | null) => {
        set(sensorFilterAtom, uuid);
    }
);
