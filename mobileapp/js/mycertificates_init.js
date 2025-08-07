// This file is part of the tool_certificate plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

const CoreNavigator = this.CoreNavigatorService;
const Md5 = this.Md5;
const CoreSites = this.CoreSitesProvider;

/**
 * Handler to treat links to the my certificates page.
 */
class ToolCertificateMyCertificatesLinkHandler extends this.CoreContentLinksHandlerBase {

    constructor() {
        super();

        this.name = 'ToolCertificateMyCertificatesLinkHandler';
        this.pattern = /\/admin\/tool\/certificate\/my\.php/;
    }

    isEnabled() {
        return true;
    }

    getActions(siteIds, url, params) {
        return [{
            action: () => {
                const args = {
                    userid: Number(params.userid) || CoreSites.getCurrentSiteUserId(),
                };
                const hash = Md5.hashAsciiStr(JSON.stringify(args));

                return CoreNavigator.navigateToSitePath(
                    `siteplugins/content/tool_certificate/mobile_my_certificates_view/${hash}`,
                    {
                        params: {
                            title: 'plugin.tool_certificate.mycertificates',
                            args,
                            contextLevel: 'user',
                            contextInstanceId: args.userid,
                        },
                    },
                );
            },
        }];
    }

}

// Register the link handler.
this.CoreContentLinksDelegate.registerHandler(new ToolCertificateMyCertificatesLinkHandler());
