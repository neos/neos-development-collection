import { IconTypesKeys } from "./IconTypesKeys";

export default interface MessageOptions {
	position?: string;
	title: string;
	message?: string;
	type: IconTypesKeys;
	timeout: number;
	closeButton?: Boolean;
}
