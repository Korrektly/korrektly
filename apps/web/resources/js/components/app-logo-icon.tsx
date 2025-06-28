import { CheckCheck } from "lucide-react";
import { SVGAttributes } from "react";

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 72 37" {...props}>
            <path
                fill="#000"
                d="M1.548 11.025a4 4 0 0 1 5.485.519l.131.158 9.94 12.665 2.151-1.61 4.941 6.296-5.418 4.054a4 4 0 0 1-2.1.785l-.047.003q-.06.004-.12.005l-.087.003a4 4 0 0 1-.198-.003l-.073-.003a4 4 0 0 1-2.933-1.523L.87 16.642l-.123-.165a4 4 0 0 1 .8-5.452M48.481.894A4.001 4.001 0 0 1 53.275 7.3L32.205 23.06l-4.941-6.295z"
            ></path>
            <path stroke="#000" strokeLinecap="round" strokeWidth="8" d="M20.98 16.858 33.33 32.59M67.841 6.783 33.345 32.59"></path>
        </svg>
    );
}
