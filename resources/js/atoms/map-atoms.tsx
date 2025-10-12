import { router } from "@inertiajs/react";
import { atom } from "jotai";

export const markerSensorActionAtom = atom(() => (uuid: string) => {
     console.log(uuid);

     return null;
});